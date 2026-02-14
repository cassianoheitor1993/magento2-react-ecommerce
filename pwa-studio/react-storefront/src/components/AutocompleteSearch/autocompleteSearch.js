import React, { useState, useEffect, useRef } from 'react';
import { useLazyQuery } from '@apollo/client';
import { string, func, bool } from 'prop-types';
import { useHistory } from 'react-router-dom';
import { Price } from '@magento/peregrine';
import { useEventingContext } from '@magento/peregrine/lib/context/eventing';
import { useStyle } from '@magento/venia-ui/lib/classify';
import { PRODUCT_SEARCH_QUERY } from './autocompleteSearch.gql.js';
import defaultClasses from './autocompleteSearch.module.css';
import OptimizedImage from '../OptimizedImage';
import {
    isAdobeEventsEnabled,
    shouldEmitSearchTelemetry
} from '../../utils/featureFlags';
import {
    probeSearchRequest,
    probeSearchResponse,
    probeSearchResultClick
} from '../../utils/kpiProbes';

/**
 * AutocompleteSearch - Enhanced search with instant results
 * Shows product suggestions as user types with images and prices
 */
const AutocompleteSearch = props => {
    const { value, onChange, onSubmit, isOpen } = props;
    const classes = useStyle(defaultClasses, props.classes);
    const history = useHistory();
    const [, { dispatch }] = useEventingContext();
    const [suggestions, setSuggestions] = useState([]);
    const [selectedIndex, setSelectedIndex] = useState(-1);
    const dropdownRef = useRef(null);
    const lastSearchResponseRef = useRef({
        signature: null,
        timestamp: 0
    });

    const [runSearch, { data, loading, called }] = useLazyQuery(
        PRODUCT_SEARCH_QUERY,
        {
            fetchPolicy: 'cache-and-network'
        }
    );

    const updateInputValue = nextValue => {
        if (!onChange) {
            return;
        }

        if (typeof nextValue === 'string') {
            onChange({ target: { value: nextValue } });
            return;
        }

        onChange(nextValue);
    };

    // Debounced search
    useEffect(() => {
        if (value && value.length > 2) {
            const timer = setTimeout(() => {
                if (isAdobeEventsEnabled && shouldEmitSearchTelemetry) {
                    dispatch({
                        type: 'SEARCHBAR_REQUEST',
                        payload: {
                            query: value,
                            currentPage: 1,
                            pageSize: 8,
                            refinements: []
                        }
                    });
                }

                if (shouldEmitSearchTelemetry) {
                    probeSearchRequest({
                        query: value,
                        pageSize: 8,
                        source: 'header_autocomplete'
                    });
                }

                runSearch({ variables: { search: value, pageSize: 8 } });
            }, 300);
            return () => clearTimeout(timer);
        } else {
            setSuggestions([]);
        }
    }, [value, runSearch, dispatch]);

    // Update suggestions when data changes
    useEffect(() => {
        if (data?.products?.items) {
            setSuggestions(data.products.items);

            const signature = `${value}|${data.products.total_count}|${
                data.products.items?.[0]?.sku || 'none'
            }`;
            const now = Date.now();
            const isDuplicateResponse =
                lastSearchResponseRef.current.signature === signature &&
                now - lastSearchResponseRef.current.timestamp < 1000;

            if (isDuplicateResponse) {
                return;
            }

            lastSearchResponseRef.current = {
                signature,
                timestamp: now
            };

            if (isAdobeEventsEnabled && shouldEmitSearchTelemetry) {
                dispatch({
                    type: 'SEARCH_RESPONSE',
                    payload: {
                        categories: [],
                        facets: [],
                        page: 1,
                        perPage: 8,
                        products: data.products.items.map(item => ({
                            sku: item.sku,
                            name: item.name,
                            urlKey: item.url_key,
                            price:
                                item?.price_range?.minimum_price?.final_price
                                    ?.value,
                            currency:
                                item?.price_range?.minimum_price?.final_price
                                    ?.currency,
                            image: item?.small_image?.url,
                            inStock: item?.stock_status === 'IN_STOCK'
                        })),
                        searchRequestId: `header-${Date.now()}`,
                        searchUnitId: 'headerSearch',
                        suggestions: []
                    }
                });
            }

            if (shouldEmitSearchTelemetry) {
                probeSearchResponse({
                    query: value,
                    totalCount: data.products.total_count,
                    displayedCount: data.products.items.length,
                    source: 'header_autocomplete'
                });
            }
        }
    }, [data, dispatch, value]);

    // Handle keyboard navigation
    const handleKeyDown = event => {
        if (!suggestions.length) return;

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                setSelectedIndex(prev =>
                    prev < suggestions.length - 1 ? prev + 1 : prev
                );
                break;
            case 'ArrowUp':
                event.preventDefault();
                setSelectedIndex(prev => (prev > 0 ? prev - 1 : -1));
                break;
            case 'Enter':
                event.preventDefault();
                if (selectedIndex >= 0 && suggestions[selectedIndex]) {
                    handleProductClick(suggestions[selectedIndex]);
                } else if (onSubmit) {
                    onSubmit(event);
                }
                break;
            case 'Escape':
                setSuggestions([]);
                setSelectedIndex(-1);
                break;
            default:
                break;
        }
    };

    const handleProductClick = product => {
        if (shouldEmitSearchTelemetry) {
            probeSearchResultClick({
                query: value,
                sku: product?.sku,
                name: product?.name,
                position:
                    suggestions.findIndex(item => item?.uid === product?.uid) + 1,
                source: 'header_autocomplete'
            });
        }

        history.push(`/${product.url_key}.html`);
        setSuggestions([]);
        setSelectedIndex(-1);
        updateInputValue('');
    };

    const formatPrice = priceData => {
        if (!priceData) return null;
        const { value, currency } = priceData.minimum_price.final_price;
        return (
            <Price value={value} currencyCode={currency} />
        );
    };

    const showSuggestions = isOpen && suggestions.length > 0;

    return (
        <div className={classes.root}>
            <input
                type="text"
                value={value}
                onChange={updateInputValue}
                onKeyDown={handleKeyDown}
                placeholder="Search for products..."
                className={classes.input}
                autoComplete="off"
                aria-label="Search"
                aria-autocomplete="list"
                aria-expanded={showSuggestions}
            />

            {showSuggestions && (
                <div
                    className={classes.dropdown}
                    ref={dropdownRef}
                    role="listbox"
                >
                    {loading && called && (
                        <div className={classes.loading}>
                            <span>Searching...</span>
                        </div>
                    )}

                    {suggestions.map((product, index) => (
                        <div
                            key={product.uid}
                            className={`${classes.suggestion} ${
                                index === selectedIndex ? classes.selected : ''
                            }`}
                            onClick={() => handleProductClick(product)}
                            role="option"
                            aria-selected={index === selectedIndex}
                        >
                            <div className={classes.imageContainer}>
                                <OptimizedImage
                                    src={product.small_image.url}
                                    alt={product.name}
                                    width={60}
                                    height={60}
                                    loading="eager"
                                />
                            </div>
                            <div className={classes.details}>
                                <span className={classes.name}>
                                    {product.name}
                                </span>
                                <div className={classes.meta}>
                                    <span className={classes.price}>
                                        {formatPrice(product.price_range)}
                                    </span>
                                    {product.rating_summary > 0 && (
                                        <span className={classes.rating}>
                                            ⭐ {(product.rating_summary / 20).toFixed(1)}
                                            {product.review_count > 0 && (
                                                <span className={classes.reviews}>
                                                    ({product.review_count})
                                                </span>
                                            )}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}

                    {data?.products?.total_count > suggestions.length && (
                        <div className={classes.viewAll}>
                            <button
                                onClick={onSubmit}
                                className={classes.viewAllButton}
                            >
                                View all {data.products.total_count} results
                            </button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

AutocompleteSearch.propTypes = {
    value: string,
    onChange: func,
    onSubmit: func,
    isOpen: bool
};

export default AutocompleteSearch;
