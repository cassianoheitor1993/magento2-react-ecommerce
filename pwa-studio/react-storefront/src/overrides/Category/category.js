import React, { Fragment, useEffect, useMemo, useState } from 'react';
import { createPortal } from 'react-dom';
import { shape, string } from 'prop-types';
import { useCategory } from '@magento/peregrine/lib/talons/RootComponents/Category';
import { useEventingContext } from '@magento/peregrine/lib/context/eventing';
import { useStyle } from '@magento/venia-ui/lib/classify';
import { useIntl } from 'react-intl';

import CategoryContent from '@magento/venia-ui/lib/RootComponents/Category/categoryContent';
import defaultClasses from '@magento/venia-ui/lib/RootComponents/Category/category.module.css';
import localClasses from './category.local.module.css';
import { Meta, Link } from '@magento/venia-ui/lib/components/Head';
import ProductFilters from '../../components/ProductFilters';
import {
    GET_CATEGORY,
    GET_FILTER_INPUTS,
    GET_PAGE_SIZE
} from './category.gql';
import ErrorView from '@magento/venia-ui/lib/components/ErrorView';
import { probeCategoryPageView } from '../../utils/kpiProbes';

const MESSAGES = new Map().set(
    'NOT_FOUND',
    "Looks like the category you were hoping to find doesn't exist. Sorry about that."
);

const Category = props => {
    const { uid } = props;
    const { formatMessage } = useIntl();
    const [, { dispatch }] = useEventingContext();

    const talonProps = useCategory({
        id: uid,
        operations: {
            getCategoryQuery: GET_CATEGORY,
            getFilterInputsQuery: GET_FILTER_INPUTS
        },
        queries: {
            getPageSize: GET_PAGE_SIZE
        }
    });

    const {
        error,
        metaDescription,
        loading,
        categoryData,
        pageControl,
        sortProps,
        pageSize,
        categoryNotFound,
        storeConfig
    } = talonProps;

    const normalizedCategoryData = useMemo(() => {
        const item = categoryData?.categories?.items?.[0];

        if (!item || item.name) {
            return categoryData;
        }

        return {
            ...categoryData,
            categories: {
                ...categoryData.categories,
                items: [
                    {
                        ...item,
                        name: item.meta_title || item.url_key || 'Shop'
                    },
                    ...(categoryData.categories?.items || []).slice(1)
                ]
            }
        };
    }, [categoryData]);

    const classes = useStyle(defaultClasses, localClasses, props.classes);
    const [filtersMountNode, setFiltersMountNode] = useState(null);

    useEffect(() => {
        if (typeof document === 'undefined') {
            return;
        }

        const resolveSidebar = () =>
            document.querySelector('[data-cy="CategoryContent-root"] [class*="sidebar"]');

        const setupMountNode = sidebar => {
            if (!sidebar) {
                return;
            }

            let mount = sidebar.querySelector('[data-custom-product-filters-mount="true"]');

            if (!mount) {
                mount = document.createElement('div');
                mount.setAttribute('data-custom-product-filters-mount', 'true');
                sidebar.appendChild(mount);
            }

            setFiltersMountNode(mount);
        };

        let cancelled = false;

        const timer = window.setInterval(() => {
            if (cancelled) {
                return;
            }

            const sidebar = resolveSidebar();
            if (sidebar) {
                setupMountNode(sidebar);
                window.clearInterval(timer);
            }
        }, 100);

        return () => {
            cancelled = true;
            window.clearInterval(timer);
        };
    }, [normalizedCategoryData, loading, pageControl.currentPage]);

    const canonicalUrl = useMemo(() => {
        if (!normalizedCategoryData || !storeConfig?.category_canonical_tag) return null;

        const category = normalizedCategoryData.categories?.items?.[0];
        if (!category) return null;

        const urlPath = category.url_path || category.url_key;
        if (!urlPath) return null;

        const origin =
            typeof window !== 'undefined' ? window.location.origin : '';
        const suffix = storeConfig?.category_url_suffix || '';

        return `${origin}/${urlPath}${suffix}`;
    }, [normalizedCategoryData, storeConfig]);

    const categoryJsonLd = useMemo(() => {
        const category = normalizedCategoryData?.categories?.items?.[0];
        if (!category) {
            return null;
        }

        const products = (normalizedCategoryData?.products?.items || []).slice(0, 10);
        const breadcrumbs = category?.breadcrumbs || [];
        const origin =
            typeof window !== 'undefined' ? window.location.origin : '';
        const url =
            canonicalUrl ||
            (typeof window !== 'undefined' ? window.location.href : undefined);

        const collectionSchema = {
            '@context': 'https://schema.org',
            '@type': 'CollectionPage',
            name: category.meta_title || category.url_key,
            description: category.meta_description || undefined,
            url,
            mainEntity: {
                '@type': 'ItemList',
                itemListElement: products.map((item, index) => ({
                    '@type': 'ListItem',
                    position: index + 1,
                    item: {
                        '@type': 'Product',
                        name: item.name,
                        sku: item.sku,
                        image: item?.small_image?.url || undefined,
                        url:
                            typeof window !== 'undefined'
                                ? `${window.location.origin}/${item.url_key}.html`
                                : undefined,
                        offers: {
                            '@type': 'Offer',
                            price:
                                item?.price_range?.maximum_price?.final_price?.value,
                            priceCurrency:
                                item?.price_range?.maximum_price?.final_price?.currency,
                            availability:
                                item.stock_status === 'IN_STOCK'
                                    ? 'https://schema.org/InStock'
                                    : 'https://schema.org/OutOfStock'
                        }
                    }
                }))
            }
        };

        if (breadcrumbs.length && origin) {
            collectionSchema.breadcrumb = {
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

        return collectionSchema;
    }, [normalizedCategoryData, canonicalUrl]);

    useEffect(() => {
        const category = normalizedCategoryData?.categories?.items?.[0];

        if (!category) {
            return;
        }

        dispatch({
            type: 'CATEGORY_PAGE_VIEW',
            payload: {
                name: category.meta_title || category.url_key,
                url_key: category.url_key,
                url_path: category.url_path
            }
        });

        probeCategoryPageView({
            source: 'category_page_view',
            uid: category.uid,
            name: category.meta_title || category.url_key,
            urlPath: category.url_path
        });
    }, [normalizedCategoryData, dispatch]);

    if (!normalizedCategoryData) {
        if (error && pageControl.currentPage === 1) {
            if (process.env.NODE_ENV !== 'production') {
                console.error(error);
            }

            return <ErrorView />;
        }
    }
    if (categoryNotFound) {
        return (
            <ErrorView
                message={formatMessage({
                    id: 'category.notFound',
                    defaultMessage: MESSAGES.get('NOT_FOUND')
                })}
            />
        );
    }

    return (
        <Fragment>
            <Meta name="description" content={metaDescription} />
            {canonicalUrl && <Link rel="canonical" href={canonicalUrl} />}
            {categoryJsonLd ? (
                <script
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{
                        __html: JSON.stringify(categoryJsonLd)
                    }}
                />
            ) : null}
            <CategoryContent
                categoryId={uid}
                classes={classes}
                data={normalizedCategoryData}
                isLoading={loading}
                pageControl={pageControl}
                sortProps={sortProps}
                pageSize={pageSize}
            />
            {filtersMountNode
                ? createPortal(
                      <ProductFilters
                          aggregations={normalizedCategoryData?.products?.aggregations || []}
                          containerTag="div"
                      />,
                      filtersMountNode
                  )
                : null}
        </Fragment>
    );
};

Category.propTypes = {
    classes: shape({
        gallery: string,
        root: string,
        title: string
    }),
    uid: string
};

Category.defaultProps = {
    uid: 'Mg=='
};

export default Category;
