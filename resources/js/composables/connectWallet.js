import { ref } from 'vue'
import { Connection, PublicKey } from '@solana/web3.js' // You may need to npm install @solana/web3.js

const buyer = ref(null)
const connecting = ref(false)
const hasReduction = ref(false) // New state

// Setup connection to Solana Mainnet
const connection = new Connection(
    "https://solana-mainnet.g.alchemy.com/v2/gWRf9jmZLpeU5o7c9r1nD",
    "confirmed"
);

export function useWallet() {
    async function checkBalance(publicKeyString) {
        try {
            const pubKey = new PublicKey(publicKeyString);
            const balance = await connection.getBalance(pubKey);
            // Example: 0.1 SOL (balance is in Lamports, so 0.1 * 10^9)
            hasReduction.value = balance >= (0.1 * 10**9);
        } catch (e) {
            hasReduction.value = false;
        }
    }

    async function connect(showToast) {
        const phantom = window.phantom?.solana;
        const provider = phantom?.isPhantom ? phantom : window.solana;

        if (!provider?.isPhantom) {
            showToast('Phantom not found. Opening install page…', 'info');
            window.open('https://phantom.app/download', '_blank', 'noopener');
            return;
        }

        try {
            connecting.value = true;
            const response = await provider.connect({ onlyIfTrusted: true })
                .catch(() => provider.connect({ onlyIfTrusted: false }))

            const publicKey = response.publicKey.toString()
            buyer.value = publicKey

            // Check for crypto balance
            await checkBalance(publicKey);

            const msg = `Cooldown confirmation for wallet ${publicKey} at ${Date.now()}`
            const signed = await provider.signMessage(new TextEncoder().encode(msg), 'utf8')

            localStorage.setItem('walletSignature', JSON.stringify({
                publicKey,
                hasReduction: hasReduction.value, // Save the status
                message: msg,
                signature: Array.from(signed.signature)
            }))

            localStorage.setItem('connectedWallet', publicKey)
            showToast(hasReduction.value ? 'Wallet connected (Reduction Active!)' : 'Wallet connected', 'success');
        } catch (err) {
            showToast('Wallet connection canceled', 'error');
        } finally {
            connecting.value = false;
        }
    }

    function loadCachedWallet() {
        const cached = localStorage.getItem('connectedWallet')
        const sig = JSON.parse(localStorage.getItem('walletSignature') || '{}')
        if (cached && window.phantom?.solana?.isConnected) {
            buyer.value = cached
            hasReduction.value = sig.hasReduction || false
        } else {
            buyer.value = null
            hasReduction.value = false
        }
    }

    return { buyer, connecting, hasReduction, connect, loadCachedWallet }
}
