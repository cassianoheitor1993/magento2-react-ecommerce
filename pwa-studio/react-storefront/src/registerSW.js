import {
    VALID_SERVICE_WORKER_ENVIRONMENT,
    handleMessageFromSW
} from '@magento/peregrine/lib/util/swUtils';

export const registerSW = () => {
    if (__DEV__ && globalThis.navigator?.serviceWorker) {
        globalThis.navigator.serviceWorker
            .getRegistrations()
            .then(registrations => {
                registrations.forEach(registration => registration.unregister());
            })
            .catch(() => {
                // Best effort cleanup in development.
            });

        return;
    }

    if (VALID_SERVICE_WORKER_ENVIRONMENT && globalThis.navigator) {
        window.navigator.serviceWorker
            .register('/sw.js')
            .then(() => {
                console.log('SW Registered');
            })
            .catch(() => {
                /**
                 * console.* statements are removed by webpack
                 * in production mode. window.console.* are not.
                 */
                window.console.warn('Failed to register SW.');
            });

        navigator.serviceWorker.addEventListener('message', e => {
            const { type, payload } = e.data;
            handleMessageFromSW(type, payload, e);
        });
    }
};
