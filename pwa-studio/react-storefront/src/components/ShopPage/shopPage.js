import React, { useMemo, useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { ChevronLeft, ChevronRight } from 'react-feather';
import { useQuery } from '@apollo/client';
import { mergeClasses } from '@magento/venia-ui/lib/classify';
import { fullPageLoadingIndicator } from '@magento/venia-ui/lib/components/LoadingIndicator';
import ErrorView from '@magento/venia-ui/lib/components/ErrorView';
import resourceUrl from '@magento/peregrine/lib/util/makeUrl';

import { GET_ALL_PRODUCTS } from '../../queries/getAllProducts.gql';
import ProductFilters from '../ProductFilters';
import defaultClasses from './shopPage.module.css';

const PAGE_SIZE = 12;
const SHOP_CATEGORY_UID = 'Ng==';
const FILTER_SUFFIX = '[filter]';

const deriveMediaBase = imageUrl => {
    const value = String(imageUrl || '');
    const marker = '/media/';
    const markerIndex = value.indexOf(marker);

    if (markerIndex === -1) {
        return '';
    }

    return value.slice(0, markerIndex);
};

const extractFilterValue = rawValue => {
    const value = String(rawValue || '');
    const parts = value.split(',');
    return (parts.pop() || '').trim();
};

const ProductCard = ({ item, classes, currency }) => {
    const [activeImageIndex, setActiveImageIndex] = useState(0);

    const images = useMemo(() => {
        const galleryWithUrls = (item.media_gallery || [])
            .filter(entry => entry && !entry.disabled && entry.url)
            .sort((a, b) => (a.position || 0) - (b.position || 0))
            .map(entry => ({
                uid: entry.uid || `gallery-${entry.position || entry.url}`,
                resource: entry.url,
                label: entry.label || item.name
            }));

        if (galleryWithUrls.length) {
            return galleryWithUrls;
        }

        const mediaBase = deriveMediaBase(item?.small_image?.url);
        const entries = (item.media_gallery_entries || [])
            .filter(entry => entry && !entry.disabled && entry.file)
            .sort((a, b) => (a.position || 0) - (b.position || 0))
            .map(entry => ({
                uid: entry.uid,
                resource:
                    String(entry.file || '').startsWith('http')
                        ? entry.file
                        : `${mediaBase}/media/catalog/product${entry.file}`,
                label: entry.label || item.name
            }));

        if (entries.length) {
            return entries;
        }

        if (item?.small_image?.url) {
            return [
                {
                    uid: `fallback-${item.uid || item.sku}`,
                    src: item.small_image.url,
                    label: item.small_image.label || item.name
                }
            ];
        }

        return [];
    }, [item]);

    useEffect(() => {
        setActiveImageIndex(0);
    }, [item.uid, images.length]);

    const hasMultipleImages = images.length > 1;
    const currentImage = images[activeImageIndex] || images[0];
    const finalPrice = item?.price_range?.minimum_price?.final_price?.value;
    const productPath = item?.url_key
        ? `/${item.url_key}${item.url_suffix || '.html'}`
        : '/';

    return (
        <article key={item.uid || item.sku} className={classes.card}>
            <Link className={classes.cardLink} to={resourceUrl(productPath)}>
                <div className={classes.imageWrap}>
                    {currentImage ? (
                        <div className={classes.imageContainer}>
                            <img
                                alt={currentImage.label || item.name}
                                className={classes.image}
                                loading="lazy"
                                src={
                                    currentImage.resource ||
                                    currentImage.src ||
                                    item?.small_image?.url
                                }
                                onError={event => {
                                    if (
                                        item?.small_image?.url &&
                                        event.currentTarget.src !== item.small_image.url
                                    ) {
                                        event.currentTarget.src = item.small_image.url;
                                    }
                                }}
                            />
                        </div>
                    ) : null}

                    {hasMultipleImages ? (
                        <>
                            <button
                                type="button"
                                className={`${classes.imageNavBtn} ${classes.imageNavPrev}`}
                                onClick={event => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    setActiveImageIndex(prev =>
                                        prev === 0 ? images.length - 1 : prev - 1
                                    );
                                }}
                                aria-label="Previous product image"
                            >
                                <ChevronLeft size={16} />
                            </button>
                            <button
                                type="button"
                                className={`${classes.imageNavBtn} ${classes.imageNavNext}`}
                                onClick={event => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    setActiveImageIndex(prev =>
                                        (prev + 1) % images.length
                                    );
                                }}
                                aria-label="Next product image"
                            >
                                <ChevronRight size={16} />
                            </button>
                        </>
                    ) : null}
                </div>

                <h2 className={classes.name}>{item.name}</h2>
                <p className={classes.price}>
                    {typeof finalPrice === 'number'
                        ? new Intl.NumberFormat(undefined, {
                              style: 'currency',
                              currency
                          }).format(finalPrice)
                        : '—'}
                </p>
            </Link>
        </article>
    );
};

const ShopPage = props => {
    const classes = mergeClasses(defaultClasses, props.classes);
    const [currentPage, setCurrentPage] = useState(1);
    const location = useLocation();

    const filters = useMemo(() => {
        const params = new URLSearchParams(location.search || '');
        const gqlFilters = {
            category_uid: { eq: SHOP_CATEGORY_UID }
        };

        const groupedValues = new Map();
        const uniqueKeys = new Set(params.keys());

        uniqueKeys.forEach(key => {
            if (!key.endsWith(FILTER_SUFFIX)) {
                return;
            }

            const attributeCode = key.slice(0, -FILTER_SUFFIX.length);
            const values = params
                .getAll(key)
                .map(extractFilterValue)
                .filter(Boolean);

            if (!values.length) {
                return;
            }

            groupedValues.set(attributeCode, values);
        });

        groupedValues.forEach((values, attributeCode) => {
            if (attributeCode === 'price') {
                const [fromRaw, toRaw] = String(values[0] || '').split('_');
                const priceFilter = {};

                if (fromRaw && fromRaw !== '*') {
                    priceFilter.from = fromRaw;
                }
                if (toRaw && toRaw !== '*') {
                    priceFilter.to = toRaw;
                }

                if (Object.keys(priceFilter).length) {
                    gqlFilters.price = priceFilter;
                }
                return;
            }

            gqlFilters[attributeCode] =
                values.length === 1 ? { eq: values[0] } : { in: values };
        });

        return gqlFilters;
    }, [location.search]);

    useEffect(() => {
        setCurrentPage(1);
    }, [location.search]);

    const { loading, error, data } = useQuery(GET_ALL_PRODUCTS, {
        variables: {
            pageSize: PAGE_SIZE,
            currentPage,
            filters
        },
        fetchPolicy: 'cache-and-network'
    });

    const productData = data?.products;
    const products = productData?.items || [];
    const totalCount = productData?.total_count || 0;
    const pageInfo = productData?.page_info || { current_page: 1, total_pages: 1 };

    const currency = useMemo(() => {
        return (
            products[0]?.price_range?.minimum_price?.final_price?.currency ||
            'USD'
        );
    }, [products]);

    if (loading && !data) return fullPageLoadingIndicator;
    if (error) return <ErrorView message={error.message} />;

    return (
        <div className={classes.root}>
            <h1 className={classes.title}>Shop</h1>
            <p className={classes.subtitle}>Products from your Shop category</p>
            <p className={classes.meta}>
                {totalCount} products • Page {pageInfo.current_page} of {pageInfo.total_pages}
            </p>

            <div className={classes.layout}>
                <div className={classes.filtersCol}>
                    <ProductFilters aggregations={productData?.aggregations || []} />
                </div>

                <div className={classes.resultsCol}>
            {!products.length ? (
                <p className={classes.empty}>No products available.</p>
            ) : (
                <div className={classes.grid}>
                    {products.map(item => (
                        <ProductCard
                            key={item.uid || item.sku}
                            item={item}
                            classes={classes}
                            currency={currency}
                        />
                    ))}
                </div>
            )}

            <div className={classes.pagination}>
                <button
                    className={classes.pageBtn}
                    type="button"
                    onClick={() => setCurrentPage(page => Math.max(1, page - 1))}
                    disabled={currentPage <= 1}
                >
                    Previous
                </button>
                <button
                    className={classes.pageBtn}
                    type="button"
                    onClick={() =>
                        setCurrentPage(page =>
                            Math.min(pageInfo.total_pages || page, page + 1)
                        )
                    }
                    disabled={currentPage >= (pageInfo.total_pages || 1)}
                >
                    Next
                </button>
            </div>
                </div>
            </div>
        </div>
    );
};

export default ShopPage;
