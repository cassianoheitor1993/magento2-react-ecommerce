import React, { useMemo, useState, useEffect } from 'react';
import { FormattedMessage } from 'react-intl';
import { Info, ChevronLeft, ChevronRight } from 'react-feather';
import { string, number, shape, arrayOf } from 'prop-types';
import { Link } from 'react-router-dom';
import Price from '@magento/venia-ui/lib/components/Price';
import { useGalleryItem } from '@magento/peregrine/lib/talons/Gallery/useGalleryItem';
import resourceUrl from '@magento/peregrine/lib/util/makeUrl';

import { useStyle } from '@magento/venia-ui/lib/classify';
import GalleryItemShimmer from '@magento/venia-ui/lib/components/Gallery/item.shimmer';
import defaultClasses from './item.module.css';
import WishlistGalleryButton from '@magento/venia-ui/lib/components/Wishlist/AddToListButton';
import AddToCartButton from '@magento/venia-ui/lib/components/Gallery/addToCartButton';

const deriveMediaBase = imageUrl => {
    const value = String(imageUrl || '');
    const marker = '/media/';
    const markerIndex = value.indexOf(marker);

    if (markerIndex === -1) {
        return '';
    }

    return value.slice(0, markerIndex);
};

const GalleryItem = props => {
    const {
        handleLinkClick,
        item,
        itemRef,
        wishlistButtonProps,
        isSupportedProductType
    } = useGalleryItem(props);

    const { storeConfig } = props;
    const productUrlSuffix = storeConfig && storeConfig.product_url_suffix;
    const classes = useStyle(defaultClasses, props.classes);

    if (!item) {
        return <GalleryItemShimmer classes={classes} />;
    }

    const {
        name,
        price_range,
        small_image,
        url_key,
        media_gallery = [],
        media_gallery_entries = []
    } = item;
    const { url: smallImageURL } = small_image || {};
    const productLink = resourceUrl(`/${url_key}${productUrlSuffix || ''}`);

    const images = useMemo(() => {
        const galleryWithUrls = (media_gallery || [])
            .filter(entry => entry && !entry.disabled && entry.url)
            .sort((a, b) => (a.position || 0) - (b.position || 0))
            .map(entry => ({
                uid: `gallery-${entry.position || entry.url}`,
                file: entry.url,
                label: entry.label || item.name,
                position: entry.position || 0,
                disabled: false
            }));

        if (galleryWithUrls.length) {
            return galleryWithUrls;
        }

        const mediaBase = deriveMediaBase(smallImageURL);
        const entries = (media_gallery_entries || [])
            .filter(entry => entry && !entry.disabled && entry.file)
            .sort((a, b) => (a.position || 0) - (b.position || 0));

        const mapped = entries.map(entry => ({
            ...entry,
            file:
                String(entry.file || '').startsWith('http')
                    ? entry.file
                    : `${mediaBase}/media/catalog/product${entry.file}`
        }));

        if (mapped.length) {
            return mapped;
        }

        if (smallImageURL) {
            return [
                {
                    uid: `fallback-${item.uid || item.id || item.sku}`,
                    src: smallImageURL,
                    label: name,
                    position: 1,
                    disabled: false
                }
            ];
        }

        return [];
    }, [media_gallery, media_gallery_entries, smallImageURL, item.uid, item.id, item.sku, item.name, name]);

    const [activeImageIndex, setActiveImageIndex] = useState(0);

    useEffect(() => {
        setActiveImageIndex(0);
    }, [item.uid, images.length]);

    const hasMultipleImages = images.length > 1;
    const currentImage = images[activeImageIndex] || images[0];
    const currentResource = currentImage?.file;
    const currentSrc = currentImage?.src;

    const showPreviousImage = () => {
        setActiveImageIndex(prev =>
            prev === 0 ? images.length - 1 : prev - 1
        );
    };

    const showNextImage = () => {
        setActiveImageIndex(prev => (prev + 1) % images.length);
    };

    const wishlistButton = wishlistButtonProps ? (
        <WishlistGalleryButton {...wishlistButtonProps} />
    ) : null;

    const addButton = isSupportedProductType ? (
        <AddToCartButton item={item} urlSuffix={productUrlSuffix} />
    ) : (
        <div className={classes.unavailableContainer}>
            <Info />
            <p>
                <FormattedMessage
                    id={'galleryItem.unavailableProduct'}
                    defaultMessage={'Currently unavailable for purchase.'}
                />
            </p>
        </div>
    );

    const currencyCode =
        price_range?.maximum_price?.final_price?.currency ||
        item.price.regularPrice.amount.currency;

    const priceSource =
        (price_range?.maximum_price?.final_price !== undefined &&
        price_range?.maximum_price?.final_price !== null
            ? price_range.maximum_price.final_price
            : item.prices.maximum.final) ||
        (price_range?.maximum_price?.regular_price !== undefined &&
        price_range?.maximum_price?.regular_price !== null
            ? price_range.maximum_price.regular_price
            : item.prices.maximum.regular);

    const priceSourceValue = priceSource.value || priceSource;

    return (
        <div data-cy="GalleryItem-root" className={classes.root} ref={itemRef}>
            <div className={classes.images}>
                <Link
                    aria-label={name}
                    onClick={handleLinkClick}
                    to={productLink}
                    className={classes.images}
                >
                    <div className={classes.imageContainer}>
                        <img
                            alt={currentImage?.label || name}
                            className={classes.image}
                            loading="lazy"
                            src={currentResource || currentSrc || smallImageURL}
                            onError={event => {
                                if (
                                    smallImageURL &&
                                    event.currentTarget.src !== smallImageURL
                                ) {
                                    event.currentTarget.src = smallImageURL;
                                }
                            }}
                        />
                    </div>
                </Link>

                {hasMultipleImages ? (
                    <>
                        <button
                            type="button"
                            className={`${classes.navButton} ${classes.navButtonPrev}`}
                            onClick={showPreviousImage}
                            aria-label="Previous product image"
                        >
                            <ChevronLeft className={classes.navIcon} />
                        </button>
                        <button
                            type="button"
                            className={`${classes.navButton} ${classes.navButtonNext}`}
                            onClick={showNextImage}
                            aria-label="Next product image"
                        >
                            <ChevronRight className={classes.navIcon} />
                        </button>
                    </>
                ) : null}
            </div>

            <Link
                onClick={handleLinkClick}
                to={productLink}
                className={classes.name}
                data-cy="GalleryItem-name"
            >
                <span>{name}</span>
            </Link>

            <div data-cy="GalleryItem-price" className={classes.price}>
                <Price value={priceSourceValue} currencyCode={currencyCode} />
            </div>

            <div className={classes.actionsContainer}>
                {addButton}
                {wishlistButton}
            </div>
        </div>
    );
};

GalleryItem.propTypes = {
    classes: shape({
        image: string,
        imageLoaded: string,
        imageNotLoaded: string,
        imageContainer: string,
        images: string,
        name: string,
        price: string,
        root: string
    }),
    item: shape({
        id: number.isRequired,
        uid: string.isRequired,
        name: string.isRequired,
        small_image: shape({
            url: string.isRequired
        }),
        media_gallery_entries: arrayOf(
            shape({
                uid: string,
                file: string,
                label: string,
                position: number
            })
        ),
        stock_status: string.isRequired,
        __typename: string.isRequired,
        url_key: string.isRequired,
        sku: string.isRequired,
        price_range: shape({
            maximum_price: shape({
                final_price: shape({
                    value: number.isRequired,
                    currency: string.isRequired
                }),
                regular_price: shape({
                    value: number.isRequired,
                    currency: string.isRequired
                }).isRequired,
                discount: shape({
                    amount_off: number.isRequired
                }).isRequired
            }).isRequired
        }).isRequired
    }),
    storeConfig: shape({
        magento_wishlist_general_is_enabled: string.isRequired,
        product_url_suffix: string
    })
};

export default GalleryItem;
