import React, { useState, useEffect } from 'react';
import { shape, string, number } from 'prop-types';
import { useStyle } from '@magento/venia-ui/lib/classify';
import defaultClasses from './socialProof.module.css';

/**
 * SocialProof - Display recent purchase notifications
 * Creates urgency and trust by showing real-time purchase activity
 */
const SocialProof = props => {
    const { productName, timeAgo = 5, count = 1, location = 'New York' } = props;
    const classes = useStyle(defaultClasses, props.classes);
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        // Show notification after a short delay
        const showTimer = setTimeout(() => {
            setIsVisible(true);
        }, 3000);

        // Auto-hide after 8 seconds
        const hideTimer = setTimeout(() => {
            setIsVisible(false);
        }, 11000);

        return () => {
            clearTimeout(showTimer);
            clearTimeout(hideTimer);
        };
    }, [productName]);

    if (!isVisible) return null;

    const message = count > 1
        ? `${count} people bought this in the last 24 hours`
        : `Someone from ${location} purchased this ${timeAgo} minutes ago`;

    return (
        <div className={classes.root} role="alert" aria-live="polite">
            <div className={classes.notification}>
                <span className={classes.icon}>🔥</span>
                <div className={classes.content}>
                    {productName && (
                        <p className={classes.product}>{productName}</p>
                    )}
                    <p className={classes.message}>{message}</p>
                </div>
                <button
                    className={classes.close}
                    onClick={() => setIsVisible(false)}
                    aria-label="Close notification"
                >
                    ✕
                </button>
            </div>
        </div>
    );
};

SocialProof.propTypes = {
    productName: string,
    timeAgo: number,
    count: number,
    location: string,
    classes: shape({
        root: string,
        notification: string,
        icon: string,
        content: string,
        product: string,
        message: string,
        close: string
    })
};

export default SocialProof;
