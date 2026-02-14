const asBool = value => `${value}`.toLowerCase() === 'true';

const asNumber = (value, fallback = 1) => {
    const parsed = Number(value);
    return Number.isNaN(parsed) ? fallback : parsed;
};

const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

export const runtimeConfig = {
    enableAdobeEvents: asBool(
        typeof ENABLE_ADOBE_EVENTS !== 'undefined' ? ENABLE_ADOBE_EVENTS : true
    ),
    enableStorefrontRevamp: asBool(
        typeof ENABLE_STOREFRONT_REVAMP !== 'undefined'
            ? ENABLE_STOREFRONT_REVAMP
            : true
    ),
    enableSignedInPersonalization: asBool(
        typeof ENABLE_SIGNED_IN_PERSONALIZATION !== 'undefined'
            ? ENABLE_SIGNED_IN_PERSONALIZATION
            : false
    ),
    signedInPersonalizationRollout: clamp(
        asNumber(
            typeof SIGNED_IN_PERSONALIZATION_ROLLOUT !== 'undefined'
                ? SIGNED_IN_PERSONALIZATION_ROLLOUT
                : 0,
            0
        ),
        0,
        100
    ),
    adobeEventSampleRate: clamp(
        asNumber(
            typeof ADOBE_EVENT_SAMPLE_RATE !== 'undefined'
                ? ADOBE_EVENT_SAMPLE_RATE
                : 1,
            1
        ),
        0,
        1
    ),
    enableKpiProbes: asBool(
        typeof ENABLE_KPI_PROBES !== 'undefined' ? ENABLE_KPI_PROBES : true
    ),
    rollbackDisableSearchTelemetry: asBool(
        typeof ROLLBACK_DISABLE_SEARCH_TELEMETRY !== 'undefined'
            ? ROLLBACK_DISABLE_SEARCH_TELEMETRY
            : false
    ),
    rollbackDisableJsonLd: asBool(
        typeof ROLLBACK_DISABLE_JSON_LD !== 'undefined'
            ? ROLLBACK_DISABLE_JSON_LD
            : false
    ),
    rollbackDisableSocialProof: asBool(
        typeof ROLLBACK_DISABLE_SOCIAL_PROOF !== 'undefined'
            ? ROLLBACK_DISABLE_SOCIAL_PROOF
            : false
    )
};

export default runtimeConfig;
