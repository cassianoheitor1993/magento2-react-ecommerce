import React, { Fragment, useEffect, useMemo } from 'react';
import { FormattedMessage } from 'react-intl';
import { string } from 'prop-types';
import { useProduct } from '@magento/peregrine/lib/talons/RootComponents/Product/useProduct';

import ErrorView from '@magento/venia-ui/lib/components/ErrorView';
import { StoreTitle, Meta, Link } from '@magento/venia-ui/lib/components/Head';
import ProductFullDetail from '@magento/venia-ui/lib/components/ProductFullDetail';
import mapProduct from '@magento/venia-ui/lib/util/mapProduct';
import ProductShimmer from '@magento/venia-ui/lib/RootComponents/Product/product.shimmer';
import productOperations from './product.gql';
import { probeProductPageView } from '../../utils/kpiProbes';

const Product = props => {
    const { __typename: productType } = props;
    const talonProps = useProduct({
        mapProduct,
        operations: productOperations
    });

    const { error, loading, product, storeConfig } = talonProps;

    useEffect(() => {
        if (!product) {
            return;
        }

        probeProductPageView({
            source: 'product_page_view',
            sku: product.sku,
            name: product.name
        });
    }, [product]);

    const canonicalUrl = useMemo(() => {
        if (!product || !storeConfig?.product_canonical_tag) return null;

        const origin =
            typeof window !== 'undefined' ? window.location.origin : '';
        const suffix = storeConfig?.product_url_suffix || '';

        return `${origin}/${product.url_key}${suffix}`;
    }, [product, storeConfig]);

    const productJsonLd = useMemo(() => {
        if (!product) {
            return null;
        }

        const finalPrice =
            product?.price_range?.maximum_price?.final_price?.value ||
            product?.price?.regularPrice?.amount?.value;
        const currency =
            product?.price_range?.maximum_price?.final_price?.currency ||
            product?.price?.regularPrice?.amount?.currency;
        const productUrl =
            canonicalUrl ||
            (typeof window !== 'undefined'
                ? window.location.href
                : undefined);
        const image = product?.small_image?.url;
        const breadcrumbs =
            product?.categories?.[0]?.breadcrumbs || [];
        const origin =
            typeof window !== 'undefined' ? window.location.origin : '';

        const schema = {
            '@context': 'https://schema.org',
            '@type': 'Product',
            name: product.name,
            sku: product.sku,
            description: product.meta_description || undefined,
            image: image ? [image] : undefined,
            url: productUrl,
            offers: {
                '@type': 'Offer',
                price: finalPrice,
                priceCurrency: currency,
                availability:
                    product.stock_status === 'IN_STOCK'
                        ? 'https://schema.org/InStock'
                        : 'https://schema.org/OutOfStock',
                url: productUrl
            }
        };

        if (product?.rating_summary && product?.review_count) {
            schema.aggregateRating = {
                '@type': 'AggregateRating',
                ratingValue: (product.rating_summary / 20).toFixed(1),
                reviewCount: product.review_count
            };
        }

        if (breadcrumbs.length && origin) {
            schema.breadcrumb = {
                '@type': 'BreadcrumbList',
                itemListElement: breadcrumbs.map((crumb, index) => ({
                    '@type': 'ListItem',
                    position: index + 1,
                    name: crumb?.category_name,
                    item:
                        crumb?.category_url_path
                            ? `${origin}/${crumb.category_url_path}`
                            : undefined
                }))
            };
        }

        return schema;
    }, [canonicalUrl, product]);

    if (loading && !product)
        return <ProductShimmer productType={productType} />;
    if (error && !product) return <ErrorView />;
    if (!product) {
        return (
            <h1>
                <FormattedMessage
                    id={'product.outOfStockTryAgain'}
                    defaultMessage={
                        'This Product is currently out of stock. Please try again later.'
                    }
                />
            </h1>
        );
    }

    return (
        <Fragment>
            <StoreTitle>{product.name}</StoreTitle>
            <Meta name="description" content={product.meta_description} />
            {canonicalUrl && <Link rel="canonical" href={canonicalUrl} />}
            {productJsonLd ? (
                <script
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{
                        __html: JSON.stringify(productJsonLd)
                    }}
                />
            ) : null}
            <ProductFullDetail product={product} />
        </Fragment>
    );
};

Product.propTypes = {
    __typename: string.isRequired
};

export default Product;
