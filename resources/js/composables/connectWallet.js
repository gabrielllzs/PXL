export async function connectWallet({ showToast, setConnecting, setBuyer }) {
    const phantom = window.phantom?.solana;
    const provider = phantom?.isPhantom ? phantom : window.solana;

    if (!provider?.isPhantom) {
        showToast('Phantom not found. Opening install page…', 'info');
        window.open('https://phantom.app/download', '_blank', 'noopener');
        return;
    }

    try {
        setConnecting(true);

        const response = await provider.connect({ onlyIfTrusted: true })
            .catch(async () => {
                return provider.connect({ onlyIfTrusted: false })
            })

        const publicKey = response.publicKey.toString()
        setBuyer(publicKey)
        localStorage.setItem('connectedWallet', publicKey)

        const msg = `Cooldown confirmation for wallet ${publicKey} at ${Date.now()}`
        const signed = await provider.signMessage(new TextEncoder().encode(msg), 'utf8')

        // Store signed message so usePixels can send it
        const walletData = {
            publicKey,
            message: msg,
            signature: Array.from(signed.signature)
        }
        localStorage.setItem('walletSignature', JSON.stringify(walletData))

        showToast('Wallet connected', 'success');
    } catch {
        showToast('Wallet connection canceled', 'error');
    } finally {
        setConnecting(false);
    }
}

export function loadCachedWallet(setBuyer) {
    const cached = localStorage.getItem('connectedWallet')
    const provider = window.phantom?.solana
    const isConnected = provider?.isPhantom && provider.isConnected

    if (cached && isConnected) {
        setBuyer(cached)
    } else {
        // Wallet disconnected externally, clear cache
        localStorage.removeItem('connectedWallet')
        localStorage.removeItem('walletSignature')
        setBuyer(null)
    }
}

function checkWalletConnection() {
    const provider = window.phantom?.solana
    if (!provider?.isPhantom) return false

    return provider.isConnected // boolean
}
