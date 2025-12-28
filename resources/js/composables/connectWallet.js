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
        const resp = await provider.connect({ onlyIfTrusted: false });
        setBuyer(resp.publicKey.toString());
        showToast('Wallet connected', 'success');
    } catch {
        showToast('Wallet connection canceled', 'error');
    } finally {
        setConnecting(false);
    }
}
