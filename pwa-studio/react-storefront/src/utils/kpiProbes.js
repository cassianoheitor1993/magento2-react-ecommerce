import runtimeConfig from './runtimeConfig';

const emitProbe = (name, payload = {}) => {
    if (typeof window === 'undefined') {
        return;
    }

    if (!runtimeConfig.enableKpiProbes) {
        return;
    }

    window.dispatchEvent(
        new CustomEvent('storefront-kpi-probe', {
            detail: {
                name,
                payload,
                timestamp: Date.now()
            }
        })
    );
};

export const probeSearchRequest = payload => {
    emitProbe('search_request', payload);
};

export const probeSearchResponse = payload => {
    emitProbe('search_response', payload);
};

export const probeSearchResultClick = payload => {
    emitProbe('search_result_click', payload);
};

export const probeProductPageView = payload => {
    emitProbe('product_page_view', payload);
};

export const probeCategoryPageView = payload => {
    emitProbe('category_page_view', payload);
};

export const probeAddToCart = payload => {
    emitProbe('add_to_cart', payload);
};

export const probeCheckoutPageView = payload => {
    emitProbe('checkout_page_view', payload);
};

export const probePlaceOrderClick = payload => {
    emitProbe('place_order_click', payload);
};

export const probeOrderConfirmationView = payload => {
    emitProbe('order_confirmation_page_view', payload);
};

export default {
    probeSearchRequest,
    probeSearchResponse,
    probeSearchResultClick,
    probeProductPageView,
    probeCategoryPageView,
    probeAddToCart,
    probeCheckoutPageView,
    probePlaceOrderClick,
    probeOrderConfirmationView
};
