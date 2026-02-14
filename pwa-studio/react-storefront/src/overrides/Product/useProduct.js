import { useQuery } from '@apollo/client';
import { useEffect, useMemo } from 'react';
import { useLocation } from 'react-router-dom';
import { useAppContext } from '@magento/peregrine/lib/context/app';

import mergeOperations from '@magento/peregrine/lib/util/shallowMerge';
import DEFAULT_OPERATIONS from '@magento/peregrine/lib/talons/RootComponents/Product/product.gql';
import { useEventingContext } from '@magento/peregrine/lib/context/eventing';

export const useProduct = props => {
    const { mapProduct } = props;

    const operations = mergeOperations(DEFAULT_OPERATIONS, props.operations);
    const { getStoreConfigData, getProductDetailQuery } = operations;
    const { pathname } = useLocation();
    const [
        ,
        {
            actions: { setPageLoading }
        }
    ] = useAppContext();

    const { data: storeConfigData } = useQuery(getStoreConfigData, {
        fetchPolicy: 'cache-first'
    });

    const urlKey = useMemo(() => {
        const slug = pathname.split('/').pop();
        const productUrlSuffix =
            storeConfigData?.storeConfig?.product_url_suffix;
        return productUrlSuffix ? slug.replace(productUrlSuffix, '') : slug;
    }, [pathname, storeConfigData?.storeConfig?.product_url_suffix]);

    const { error, loading, data } = useQuery(getProductDetailQuery, {
        fetchPolicy: 'cache-and-network',
        nextFetchPolicy: 'cache-first',
        returnPartialData: true,
        skip: !storeConfigData,
        variables: {
            urlKey
        }
    });

    const isBackgroundLoading = !!data && loading;

    const product = useMemo(() => {
        if (!data) {
            return null;
        }

        const found = data.products.items.find(
            item => item.url_key === urlKey
        );

        if (!found) {
            return null;
        }

        return mapProduct(found);
    }, [data, mapProduct, urlKey]);

    const [, { dispatch }] = useEventingContext();

    useEffect(() => {
        setPageLoading(isBackgroundLoading);
    }, [isBackgroundLoading, setPageLoading]);

    useEffect(() => {
        if (!error && !loading && product) {
            dispatch({
                type: 'PRODUCT_PAGE_VIEW',
                payload: {
                    id: product.id,
                    name: product.name,
                    sku: product.sku,
                    currency_code:
                        product?.price_range?.maximum_price?.final_price
                            ?.currency,
                    price: product.price,
                    price_range: {
                        maximum_price: {
                            final_price:
                                product?.price_range?.maximum_price?.final_price
                                    ?.value
                        }
                    },
                    url_key: product.url_key
                }
            });
        }
    }, [error, loading, product, dispatch]);

    return {
        error,
        loading,
        product,
        storeConfig: storeConfigData?.storeConfig
    };
};
