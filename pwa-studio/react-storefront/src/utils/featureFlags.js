import runtimeConfig from './runtimeConfig';

export const isAdobeEventsEnabled = runtimeConfig.enableAdobeEvents;

export const isStorefrontRevampEnabled = runtimeConfig.enableStorefrontRevamp;

export const isSignedInPersonalizationEnabled =
    runtimeConfig.enableSignedInPersonalization;

export const signedInPersonalizationRollout =
    runtimeConfig.signedInPersonalizationRollout;

export const adobeEventSampleRate = runtimeConfig.adobeEventSampleRate;

export const shouldEmitSearchTelemetry =
    !runtimeConfig.rollbackDisableSearchTelemetry;

export const shouldRenderJsonLd = !runtimeConfig.rollbackDisableJsonLd;

export const shouldRenderSocialProof = !runtimeConfig.rollbackDisableSocialProof;

const CRITICAL_EVENTS = new Set([
    'CART_ADD_ITEM',
    'CHECKOUT_PAGE_VIEW',
    'CHECKOUT_PLACE_ORDER_BUTTON_CLICKED',
    'ORDER_CONFIRMATION_PAGE_VIEW',
    'PRODUCT_PAGE_VIEW',
    'SEARCH_REQUEST',
    'SEARCHBAR_REQUEST',
    'SEARCH_RESPONSE'
]);

export const shouldSampleAdobeEvent = eventType => {
    if (adobeEventSampleRate >= 1 || CRITICAL_EVENTS.has(eventType)) {
        return true;
    }

    return Math.random() <= adobeEventSampleRate;
};

const stableHash = value => {
    const text = `${value || ''}`;
    let hash = 0;

    for (let i = 0; i < text.length; i++) {
        hash = (hash << 5) - hash + text.charCodeAt(i);
        hash |= 0;
    }

    return Math.abs(hash % 100);
};

export const isSignedInUserEligible = currentUser => {
    if (!isSignedInPersonalizationEnabled) {
        return false;
    }

    if (signedInPersonalizationRollout >= 100) {
        return true;
    }

    const stableId =
        currentUser?.email || currentUser?.id || currentUser?.firstname || 'guest';

    return stableHash(stableId) < signedInPersonalizationRollout;
};

export default {
    isAdobeEventsEnabled,
    isStorefrontRevampEnabled,
    isSignedInPersonalizationEnabled,
    signedInPersonalizationRollout,
    adobeEventSampleRate,
    shouldEmitSearchTelemetry,
    shouldRenderJsonLd,
    shouldRenderSocialProof,
    shouldSampleAdobeEvent,
    isSignedInUserEligible
};
