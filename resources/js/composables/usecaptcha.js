let hcaptchaLoaded = false;
let hcaptchaLoading = false;
let hcaptchaReadyCallback = null;

// Set up onload callback before loading script
window.hcaptchaOnLoad = function() {
    hcaptchaLoaded = true;
    hcaptchaLoading = false;
    if (hcaptchaReadyCallback) {
        hcaptchaReadyCallback();
        hcaptchaReadyCallback = null;
    }
};

function loadHCaptcha() {
    return new Promise((resolve, reject) => {
        if (window.hcaptcha && hcaptchaLoaded) {
            resolve();
            return;
        }

        if (hcaptchaLoading) {
            // Wait for existing load
            hcaptchaReadyCallback = resolve;
            return;
        }

        hcaptchaLoading = true;
        const script = document.createElement('script');
        // Use render=explicit with onload callback
        script.src = 'https://js.hcaptcha.com/1/api.js?hl=en&render=explicit&onload=hcaptchaOnLoad';
        script.async = true;
        script.defer = true;
        script.onerror = () => {
            hcaptchaLoading = false;
            hcaptchaReadyCallback = null;
            reject(new Error('Failed to load hCaptcha'));
        };
        
        hcaptchaReadyCallback = () => {
            resolve();
        };
        
        document.head.appendChild(script);
    });
}

export function executeHCaptcha() {
    return loadHCaptcha().then(() => {
        return new Promise((resolve, reject) => {
            const container = document.getElementById('hcaptcha-container');
            if (!container || !window.hcaptcha) {
                reject(new Error('hCaptcha not ready'));
                return;
            }

        const siteKey = import.meta.env.VITE_CAPTCHA_SITE

        const onSuccess = token => resolve(token);
        const onError = err => reject(err);

        let widgetId = container.dataset.widgetId
            ? Number(container.dataset.widgetId)
            : null;

        if (widgetId === null) {
            widgetId = window.hcaptcha.render(container, {
                sitekey: siteKey,
                size: 'invisible',
                callback: onSuccess,
                'error-callback': onError
            });
            container.dataset.widgetId = String(widgetId);
        } else {
            window.hcaptcha.reset(widgetId);
        }

        window.hcaptcha.execute(widgetId);
        });
    });
}
