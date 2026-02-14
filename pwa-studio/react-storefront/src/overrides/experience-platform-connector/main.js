import { useEventingContext } from '@magento/peregrine/lib/context/eventing';
import { useUserContext } from '@magento/peregrine/lib/context/user';
import { useEffect, useState } from 'react';
import { default as handleEvent } from '@magento/experience-platform-connector/src/handleEvent';
import useExtensionContext from '@magento/experience-platform-connector/src/hooks/useExtensionContext';
import {
    isAdobeEventsEnabled,
    isSignedInUserEligible,
    shouldSampleAdobeEvent
} from '../../utils/featureFlags';
import {
    probeAddToCart,
    probeCheckoutPageView,
    probeOrderConfirmationView,
    probePlaceOrderClick
} from '../../utils/kpiProbes';

export default original => props => {
    const [{ isSignedIn, currentUser }] = useUserContext();
    const [observable] = useEventingContext();
    const isSignedInEligible = isSignedIn && isSignedInUserEligible(currentUser);

    const [sdk, setSdk] = useState();

    const {
        data: storefrontData,
        ready: storefrontDataReady,
        error
    } = useExtensionContext();

    useEffect(() => {
        if (!isAdobeEventsEnabled) {
            return;
        }

        if (error) {
            console.error('Experience Platform Connector Error', error);
            return;
        }

        if (storefrontDataReady && storefrontData) {
            const {
                dataServicesStorefrontInstanceContext: storefrontContext,
                experienceConnectorContext: connectorContext
            } = storefrontData;

            // Fix: Check if contexts exist (they don't in Magento Open Source)
            if (!storefrontContext || !connectorContext) {
                return;
            }

            import('@adobe/magento-storefront-events-sdk').then(mse => {
                if (!window.magentoStorefrontEvents) {
                    window.magentoStorefrontEvents = mse;
                }

                const orgId = storefrontContext.ims_org_id;
                const datastreamId = connectorContext.datastream_id;

                if (orgId && datastreamId) {
                    mse.context.setAEP({
                        imsOrgId: orgId,
                        datastreamId: datastreamId
                    });

                    mse.context.setEventForwarding({
                        aep: true
                    });

                    // Set storefront context
                    mse.context.setStorefrontInstance({
                        environmentId: storefrontContext.environment_id,
                        environment: storefrontContext.environment,
                        storeUrl: storefrontContext.store_url,
                        websiteId: storefrontContext.website_id,
                        websiteCode: storefrontContext.website_code,
                        storeId: storefrontContext.store_id,
                        storeCode: storefrontContext.store_code,
                        storeViewId: storefrontContext.store_view_id,
                        storeViewCode: storefrontContext.store_view_code,
                        websiteName: storefrontContext.website_name,
                        storeName: storefrontContext.store_name,
                        storeViewName: storefrontContext.store_view_name,
                        baseCurrencyCode: storefrontContext.base_currency_code,
                        storeViewCurrencyCode:
                            storefrontContext.store_view_currency_code,
                        catalogExtensionVersion:
                            storefrontContext.catalog_extension_version
                    });
                }

                // Set shopper context
                mse.context.setShopper({
                    shopperId: isSignedInEligible ? 'logged-in' : 'guest'
                });

                if (isSignedInEligible && currentUser) {
                    mse.context.setAccount({
                        firstName: currentUser.firstname,
                        lastName: currentUser.lastname,
                        emailAddress: currentUser.email,
                        accountType: currentUser.__typename
                    });
                }

                setSdk(mse);
            });
        }
    }, [
        storefrontDataReady,
        storefrontData,
        error,
        isSignedInEligible,
        currentUser
    ]);

    useEffect(() => {
        if (!isAdobeEventsEnabled || !sdk) {
            return;
        }

        const subscription = observable.subscribe(async event => {
            switch (event?.type) {
                case 'CART_ADD_ITEM':
                    probeAddToCart({
                        source: 'eventing',
                        sku: event?.payload?.cartItem?.product?.sku,
                        quantity: event?.payload?.cartItem?.quantity
                    });
                    break;
                case 'CHECKOUT_PAGE_VIEW':
                    probeCheckoutPageView({
                        source: 'eventing'
                    });
                    break;
                case 'CHECKOUT_PLACE_ORDER_BUTTON_CLICKED':
                    probePlaceOrderClick({
                        source: 'eventing'
                    });
                    break;
                case 'ORDER_CONFIRMATION_PAGE_VIEW':
                    probeOrderConfirmationView({
                        source: 'eventing',
                        orderId: event?.payload?.order_number
                    });
                    break;
                default:
                    break;
            }

            if (!shouldSampleAdobeEvent(event?.type)) {
                return;
            }

            handleEvent(sdk, event);
        });

        return () => {
            subscription.unsubscribe();
        };
    }, [observable, sdk]);

    return original(props);
};
