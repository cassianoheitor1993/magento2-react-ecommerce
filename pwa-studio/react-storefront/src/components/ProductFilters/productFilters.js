import React, { useMemo, useState } from 'react';
import { useHistory, useLocation } from 'react-router-dom';
import { useQuery } from '@apollo/client';
import { mergeClasses } from '@magento/venia-ui/lib/classify';
import { GET_NAVIGATION_CATEGORIES } from '../../queries/getNavigationCategories.gql';
import defaultClasses from './productFilters.module.css';

const EXCLUDED_FILTER_CODES = new Set(['category_id']);
const FILTER_SUFFIX = '[filter]';

const toFilterParamKey = attributeCode => `${attributeCode}${FILTER_SUFFIX}`;

const toFilterParamValue = option => `${option.label},${option.value}`;

const fromFilterParamValue = rawValue => {
    const value = String(rawValue || '');
    const parts = value.split(',');
    return (parts.pop() || '').trim();
};

const flattenCategories = nodes => {
    const list = [];
    (nodes || []).forEach(node => {
        if (!node) {
            return;
        }

        list.push(node);
        if (Array.isArray(node.children) && node.children.length) {
            list.push(...flattenCategories(node.children));
        }
    });

    return list;
};

const ProductFilters = props => {
    const { aggregations = [], classes: propClasses, containerTag = 'aside' } = props;
    const classes = mergeClasses(defaultClasses, propClasses);
    const location = useLocation();
    const history = useHistory();
    const [isMobileOpen, setIsMobileOpen] = useState(false);

    const { data: navData } = useQuery(GET_NAVIGATION_CATEGORIES, {
        fetchPolicy: 'cache-first'
    });

    const extraCategoryOptions = useMemo(() => {
        const allCategories = flattenCategories(navData?.categoryList || []);

        const nonEmptyCategories = allCategories.filter(item => {
            const productCount = item?.product_count;
            return typeof productCount === 'number' && productCount > 0;
        });

        const seen = new Set();

        return nonEmptyCategories
            .filter(item => {
                if (!item?.uid || !item?.name || !item?.include_in_menu) {
                    return false;
                }

                const urlPath = String(item.url_path || '').toLowerCase();
                const name = String(item.name || '').toLowerCase();

                const isRelevantCategory =
                    urlPath === 'shop' ||
                    urlPath.startsWith('categories') ||
                    name.includes('promo') ||
                    name.includes("what's new") ||
                    name.includes('what new');

                if (!isRelevantCategory) {
                    return false;
                }

                if (seen.has(item.uid)) {
                    return false;
                }

                seen.add(item.uid);
                return true;
            })
            .map(item => ({
                label: item.name,
                value: item.uid,
                count: 0
            }));
    }, [navData]);

    const visibleAggregations = useMemo(() => {
        const baseAggregations = (aggregations || []).filter(agg => {
            if (!agg?.attribute_code || EXCLUDED_FILTER_CODES.has(agg.attribute_code)) {
                return false;
            }

            return Array.isArray(agg.options) && agg.options.length > 0;
        });

        const merged = baseAggregations.map(agg => {
            if (agg.attribute_code !== 'category_uid' || !extraCategoryOptions.length) {
                return agg;
            }

            const existingValues = new Set((agg.options || []).map(option => String(option.value)));
            const mergedOptions = [
                ...(agg.options || []),
                ...extraCategoryOptions.filter(option => !existingValues.has(String(option.value)))
            ];

            return {
                ...agg,
                options: mergedOptions
            };
        });

        const hasCategoryUid = merged.some(agg => agg.attribute_code === 'category_uid');
        if (!hasCategoryUid && extraCategoryOptions.length) {
            merged.unshift({
                attribute_code: 'category_uid',
                label: 'Category',
                options: extraCategoryOptions
            });
        }

        return merged;
    }, [aggregations, extraCategoryOptions]);

    const getSelectedValues = attributeCode => {
        const params = new URLSearchParams(location.search || '');
        const key = toFilterParamKey(attributeCode);
        const values = params.getAll(key).map(fromFilterParamValue).filter(Boolean);
        return Array.from(new Set(values));
    };

    const updateSearchParams = callback => {
        const params = new URLSearchParams(location.search || '');
        callback(params);
        params.delete('page');

        const search = params.toString();
        history.push({
            pathname: location.pathname,
            search: search ? `?${search}` : ''
        });
    };

    const toggleOption = (attributeCode, value) => {
        const key = toFilterParamKey(attributeCode);

        updateSearchParams(params => {
            const current = new Set(getSelectedValues(attributeCode));
            if (current.has(value)) {
                current.delete(value);
            } else {
                current.add(value);
            }

            params.delete(key);

            if (!current.size) {
                return;
            } else {
                const optionsByValue = new Map(
                    (
                        visibleAggregations.find(
                            agg => agg.attribute_code === attributeCode
                        )?.options || []
                    ).map(option => [String(option.value), option])
                );

                Array.from(current).forEach(selectedValue => {
                    const option = optionsByValue.get(String(selectedValue));
                    if (!option) {
                        return;
                    }

                    params.append(key, toFilterParamValue(option));
                });
            }
        });
    };

    const clearAll = () => {
        updateSearchParams(params => {
            visibleAggregations.forEach(agg => {
                params.delete(toFilterParamKey(agg.attribute_code));
            });
        });
    };

    if (!visibleAggregations.length) {
        return null;
    }

    const ContainerTag = containerTag;

    const renderFiltersPanel = () => (
        <div className={classes.panel}>
            <div className={classes.header}>
                <strong className={classes.title}>Filters</strong>
                <button type="button" className={classes.clearBtn} onClick={clearAll}>
                    Clear all
                </button>
            </div>

            {visibleAggregations.map(aggregation => {
                const selectedValues = new Set(
                    getSelectedValues(aggregation.attribute_code)
                );

                return (
                    <div className={classes.section} key={aggregation.attribute_code}>
                        <div className={classes.sectionTitle}>{aggregation.label}</div>
                        {aggregation.options.map(option => {
                            const optionValue = String(option.value);

                            return (
                                <label
                                    className={classes.option}
                                    key={`${aggregation.attribute_code}-${optionValue}`}
                                >
                                    <input
                                        type="checkbox"
                                        checked={selectedValues.has(optionValue)}
                                        onChange={() =>
                                            toggleOption(
                                                aggregation.attribute_code,
                                                optionValue
                                            )
                                        }
                                    />
                                    <span>{option.label}</span>
                                    {typeof option.count === 'number' ? (
                                        <span className={classes.count}>
                                            ({option.count})
                                        </span>
                                    ) : null}
                                </label>
                            );
                        })}
                    </div>
                );
            })}
        </div>
    );

    return (
        <ContainerTag className={classes.root} aria-label="Product filters">
            <button
                type="button"
                className={classes.mobileToggle}
                onClick={() => setIsMobileOpen(true)}
            >
                Filters
            </button>

            <div className={classes.desktopPanel}>{renderFiltersPanel()}</div>

            {isMobileOpen ? (
                <div className={classes.mobileModalOverlay}>
                    <div className={classes.mobileModal}>
                        <div className={classes.mobileModalHeader}>
                            <strong className={classes.title}>Filters</strong>
                            <button
                                type="button"
                                className={classes.mobileCloseBtn}
                                onClick={() => setIsMobileOpen(false)}
                            >
                                Close
                            </button>
                        </div>
                        <div className={classes.mobileModalBody}>
                            {renderFiltersPanel()}
                        </div>
                        <div className={classes.mobileModalFooter}>
                            <button
                                type="button"
                                className={classes.mobileApplyBtn}
                                onClick={() => setIsMobileOpen(false)}
                            >
                                Apply Filters
                            </button>
                        </div>
                    </div>
                    <button
                        type="button"
                        className={classes.mobileBackdrop}
                        onClick={() => setIsMobileOpen(false)}
                        aria-label="Close filters"
                    >
                        Close filters
                    </button>
                </div>
            ) : null}
        </ContainerTag>
    );
};

export default ProductFilters;
