import { ref } from 'vue'

const buyer = ref(null)
const connecting = ref(false)
const hasReduction = ref(false)
const walletSignature = ref(null)

export function useWallet() {
    async function connect(showToast) {
        const phantom = window.phantom?.solana
        const provider = phantom?.isPhantom ? phantom : window.solana

        if (!provider?.isPhantom) {
            showToast('Phantom not found. Opening install page…', 'info')
            window.open('https://phantom.app/download', '_blank', 'noopener')
            return
        }

        try {
            connecting.value = true
            const response = await provider.connect({ onlyIfTrusted: true }).catch(() => provider.connect({ onlyIfTrusted: false }))
            const publicKey = response.publicKey.toString()

            const message = `Cooldown confirmation for wallet on pixel-art.design`

            const signed = await provider.signMessage(new TextEncoder().encode(message), 'utf8')

            const signatureArray = Array.from(signed.signature)

            await checkBalance(publicKey, signatureArray, message)

            // Update state
            buyer.value = publicKey
            walletSignature.value = { message, signature: signatureArray }

            localStorage.setItem('walletSignature', JSON.stringify({ publicKey, message, signature: signatureArray }))
            localStorage.setItem('connectedWallet', publicKey)

            showToast(
                hasReduction.value ? 'Wallet connected (Reduction Active!)' : 'Wallet connected (No Reduction)',
                'success'
            )
        } catch (err) {
            console.error('Wallet connection error:', err)
            showToast('Wallet connection canceled (make sure to accept sign validation for a cooldown reduction)', 'error')
        } finally {
            connecting.value = false
        }
    }

    async function checkBalance(publicKey, signature, message) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            const response = await fetch('/api/wallet/check-balance', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ publicKey, signature, message })
            })
            const data = await response.json()

            if (!data.signatureValid) throw new Error('Invalid signature')

            hasReduction.value = data.hasReduction || false
        } catch (e) {
            console.error('Balance check failed:', e)
            hasReduction.value = false
            throw e
        }
    }

    function loadCachedWallet() {
        const cached = localStorage.getItem('connectedWallet')
        const sig = JSON.parse(localStorage.getItem('walletSignature') || '{}')

        if (cached && window.phantom?.solana?.isConnected) {
            buyer.value = cached
            hasReduction.value = sig.hasReduction || false
            if (sig.message && sig.signature) walletSignature.value = { message: sig.message, signature: sig.signature }
        } else {
            buyer.value = null
            hasReduction.value = false
            walletSignature.value = null
        }
    }

    return { buyer, connecting, hasReduction, walletSignature, connect, loadCachedWallet }
}
