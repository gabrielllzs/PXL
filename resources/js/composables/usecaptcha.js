let hcaptchaLoaded = false;
let hcaptchaLoading = false;

function loadHCaptcha() {
    return new Promise((resolve, reject) => {
        if (window.hcaptcha) {
            resolve();
            return;
        }
        
        if (hcaptchaLoading) {
            // Wait for existing load
            const checkInterval = setInterval(() => {
                if (window.hcaptcha) {
                    clearInterval(checkInterval);
                    resolve();
                }
            }, 100);
            return;
        }
        
        hcaptchaLoading = true;
        const script = document.createElement('script');
        script.src = 'https://js.hcaptcha.com/1/api.js?hl=en';
        script.async = true;
        script.defer = true;
        script.onload = () => {
            hcaptchaLoaded = true;
            hcaptchaLoading = false;
            resolve();
        };
        script.onerror = () => {
            hcaptchaLoading = false;
            reject(new Error('Failed to load hCaptcha'));
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
