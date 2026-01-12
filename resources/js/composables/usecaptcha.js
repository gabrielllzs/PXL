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

            let widgetId = container.dataset.widgetId
                ? Number(container.dataset.widgetId)
                : null;

            // Set up callbacks that will be used
            let resolved = false;
            const onSuccess = token => {
                if (!resolved) {
                    resolved = true;
                    resolve(token);
                }
            };
            const onError = err => {
                if (!resolved) {
                    resolved = true;
                    reject(err);
                }
            };

            if (widgetId === null) {
                // First time: render the widget
                widgetId = window.hcaptcha.render(container, {
                    sitekey: siteKey,
                    size: 'invisible',
                    callback: onSuccess,
                    'error-callback': onError
                });
                container.dataset.widgetId = String(widgetId);
            } else {
                // Reset the widget for subsequent calls
                window.hcaptcha.reset(widgetId);
            }

            // Execute the widget
            window.hcaptcha.execute(widgetId);

            // Poll for response as fallback (in case callbacks don't fire after reset)
            // This ensures we get the token even if callbacks aren't triggered
            const pollInterval = setInterval(() => {
                try {
                    const response = window.hcaptcha.getResponse(widgetId);
                    if (response && !resolved) {
                        resolved = true;
                        clearInterval(pollInterval);
                        resolve(response);
                    }
                } catch (e) {
                    // Continue polling
                }
            }, 100);

            // Timeout after 30 seconds
            setTimeout(() => {
                if (!resolved) {
                    resolved = true;
                    clearInterval(pollInterval);
                    reject(new Error('Captcha timeout'));
                }
            }, 30000);
        });
    });
}
