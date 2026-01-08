export function executeHCaptcha() {
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
}
