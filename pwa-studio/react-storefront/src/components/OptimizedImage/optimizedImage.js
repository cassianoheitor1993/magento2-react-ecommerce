import React from 'react';
import { string, number, oneOf } from 'prop-types';
import { useStyle } from '@magento/venia-ui/lib/classify';
import defaultClasses from './optimizedImage.module.css';

/**
 * OptimizedImage component with WebP support and lazy loading
 * @param {string} src - Image source URL
 * @param {string} alt - Alt text for accessibility
 * @param {number} width - Image width
 * @param {number} height - Image height
 * @param {string} loading - Loading strategy: 'lazy' or 'eager'
 * @param {string} objectFit - CSS object-fit property
 */
const OptimizedImage = props => {
    const {
        src,
        alt,
        width,
        height,
        loading = 'lazy',
        objectFit = 'cover',
        className
    } = props;

    const classes = useStyle(defaultClasses, props.classes);

    // Generate WebP source if image is jpg/png
    const webpSrc = src?.replace(/\.(jpg|jpeg|png)$/i, '.webp');
    const isConvertible = /\.(jpg|jpeg|png)$/i.test(src);

    const imgStyle = {
        objectFit,
        width: width ? `${width}px` : '100%',
        height: height ? `${height}px` : 'auto'
    };

    return (
        <picture className={`${classes.root} ${className || ''}`}>
            {isConvertible && (
                <source srcSet={webpSrc} type="image/webp" />
            )}
            <source srcSet={src} type={`image/${src?.split('.').pop()}`} />
            <img
                src={src}
                alt={alt}
                width={width}
                height={height}
                loading={loading}
                decoding="async"
                style={imgStyle}
                className={classes.image}
            />
        </picture>
    );
};

OptimizedImage.propTypes = {
    src: string.isRequired,
    alt: string.isRequired,
    width: number,
    height: number,
    loading: oneOf(['lazy', 'eager']),
    objectFit: oneOf(['cover', 'contain', 'fill', 'none', 'scale-down']),
    className: string
};

export default OptimizedImage;
