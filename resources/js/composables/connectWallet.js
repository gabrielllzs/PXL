import { ref } from 'vue'

const buyer = ref(null)
const connecting = ref(false)
const hasReduction = ref(false)
const walletSignature = ref(null)


export function useWallet() {
    async function checkBalance(publicKeyString) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

            const response = await fetch('/api/wallet/check-balance', {
                method:  'POST',
                headers:  {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON. stringify({ publicKey: publicKeyString })
            })

            const data = await response.json()
            hasReduction.value = data.hasReduction || false
            console.log('Balance check result:', data)
        } catch (e) {
            console.error('Balance check failed:', e)
            hasReduction.value = false
        }
    }

    async function connect(showToast) {
        const phantom = window.phantom?. solana
        const provider = phantom?.isPhantom ?  phantom : window.solana

        if (!provider?.isPhantom) {
            showToast('Phantom not found. Opening install page…', 'info')
            window.open('https://phantom.app/download', '_blank', 'noopener')
            return
        }

        try {
            connecting.value = true
            const response = await provider.connect({ onlyIfTrusted: true })
                .catch(() => provider.connect({ onlyIfTrusted: false }))

            const publicKey = response.publicKey. toString()
            buyer.value = publicKey

            // Check for crypto balance
            await checkBalance(publicKey)

            const msg = `Cooldown confirmation for wallet ${publicKey} at ${Date.now()}`
            const signed = await provider.signMessage(new TextEncoder().encode(msg), 'utf8')

            // Store signature data in the ref
            walletSignature.value = {
                message: msg,
                signature: Array. from(signed.signature)
            }

            localStorage.setItem('walletSignature', JSON.stringify({
                publicKey,
                message: msg,
                signature: Array.from(signed.signature)
            }))

            localStorage.setItem('connectedWallet', publicKey)
            showToast(
                hasReduction.value
                    ? 'Wallet connected (Reduction Active!)'
                    : 'Wallet connected (No Reduction)',
                'success'
            )
        } catch (err) {
            console.error('Wallet connection error:', err)
            showToast('Wallet connection canceled', 'error')
        } finally {
            connecting.value = false
        }
    }

    function loadCachedWallet() {
        const cached = localStorage.getItem('connectedWallet')
        const sig = JSON.parse(localStorage.getItem('walletSignature') || '{}')

        if (cached && window.phantom?.solana?.isConnected) {
            buyer.value = cached
            hasReduction.value = sig.hasReduction || false

            // Restore walletSignature from localStorage
            if (sig.message && sig.signature) {
                walletSignature. value = {
                    message:  sig.message,
                    signature: sig.signature
                }
            }
        } else {
            buyer.value = null
            hasReduction.value = false
            walletSignature.value = null
        }
    }

    return {
        buyer,
        connecting,
        hasReduction,
        walletSignature,
        connect,
        loadCachedWallet
    }
}
