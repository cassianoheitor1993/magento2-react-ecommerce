/**
 * Analytics Tracking Utilities
 * Google Analytics 4 + Enhanced Ecommerce tracking
 */

/**
 * Initialize Google Analytics
 * Add this to your index.html or use a tag manager
 */
export const initGA = (measurementId) => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('config', measurementId, {
            send_page_view: false // We'll handle page views manually
        });
    }
};

/**
 * Track page views
 */
export const trackPageView = (path, title) => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'page_view', {
            page_path: path,
            page_title: title
        });
    }
};

/**
 * Track product impressions (product list views)
 */
export const trackProductImpression = (products, listName = 'Search Results') => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'view_item_list', {
            item_list_name: listName,
            items: products.map((product, index) => ({
                item_id: product.sku,
                item_name: product.name,
                price: product.price_range?.minimum_price?.final_price?.value,
                currency: product.price_range?.minimum_price?.final_price?.currency || 'USD',
                item_category: product.categories?.[0]?.name,
                item_list_name: listName,
                index: index
            }))
        });
    }
};

/**
 * Track product detail views
 */
export const trackProductView = (product) => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'view_item', {
            currency: product.price_range?.minimum_price?.final_price?.currency || 'USD',
            value: product.price_range?.minimum_price?.final_price?.value,
            items: [{
                item_id: product.sku,
                item_name: product.name,
                price: product.price_range?.minimum_price?.final_price?.value,
                item_category: product.categories?.[0]?.name,
                item_brand: product.brand || ''
            }]
        });
    }
};

/**
 * Track add to cart events
 */
export const trackAddToCart = (product, quantity = 1) => {
    if (typeof window !== 'undefined' && window.gtag) {
        const price = product.price_range?.minimum_price?.final_price?.value || product.price || 0;
        
        window.gtag('event', 'add_to_cart', {
            currency: product.price_range?.minimum_price?.final_price?.currency || 'USD',
            value: price * quantity,
            items: [{
                item_id: product.sku,
                item_name: product.name,
                price: price,
                quantity: quantity,
                item_category: product.categories?.[0]?.name
            }]
        });
    }
};

/**
 * Track remove from cart events
 */
export const trackRemoveFromCart = (product, quantity = 1) => {
    if (typeof window !== 'undefined' && window.gtag) {
        const price = product.price_range?.minimum_price?.final_price?.value || product.price || 0;
        
        window.gtag('event', 'remove_from_cart', {
            currency: product.price_range?.minimum_price?.final_price?.currency || 'USD',
            value: price * quantity,
            items: [{
                item_id: product.sku,
                item_name: product.name,
                price: price,
                quantity: quantity
            }]
        });
    }
};

/**
 * Track begin checkout
 */
export const trackBeginCheckout = (cartItems, totalValue) => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'begin_checkout', {
            currency: 'USD',
            value: totalValue,
            items: cartItems.map(item => ({
                item_id: item.sku,
                item_name: item.name,
                price: item.price,
                quantity: item.quantity
            }))
        });
    }
};

/**
 * Track purchase/order completion
 */
export const trackPurchase = (order) => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'purchase', {
            transaction_id: order.id,
            value: order.total,
            tax: order.tax,
            shipping: order.shipping,
            currency: order.currency || 'USD',
            items: order.items.map(item => ({
                item_id: item.sku,
                item_name: item.name,
                price: item.price,
                quantity: item.quantity,
                item_category: item.category
            }))
        });
    }
};

/**
 * Track search queries
 */
export const trackSearch = (searchTerm, resultsCount = 0) => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'search', {
            search_term: searchTerm,
            results_count: resultsCount
        });
    }
};

/**
 * Track user signup
 */
export const trackSignup = (method = 'email') => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'sign_up', {
            method: method
        });
    }
};

/**
 * Track user login
 */
export const trackLogin = (method = 'email') => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'login', {
            method: method
        });
    }
};

/**
 * Track custom events
 */
export const trackEvent = (eventName, eventParams = {}) => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', eventName, eventParams);
    }
};

/**
 * Track exceptions/errors
 */
export const trackException = (description, fatal = false) => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'exception', {
            description: description,
            fatal: fatal
        });
    }
};

/**
 * Set user properties
 */
export const setUserProperties = (properties) => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('set', 'user_properties', properties);
    }
};

/**
 * Track timing (performance metrics)
 */
export const trackTiming = (name, value, category = 'Performance') => {
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'timing_complete', {
            name: name,
            value: value,
            event_category: category
        });
    }
};

export default {
    initGA,
    trackPageView,
    trackProductImpression,
    trackProductView,
    trackAddToCart,
    trackRemoveFromCart,
    trackBeginCheckout,
    trackPurchase,
    trackSearch,
    trackSignup,
    trackLogin,
    trackEvent,
    trackException,
    setUserProperties,
    trackTiming
};
