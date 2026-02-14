import React from 'react';
import { shape, string } from 'prop-types';
import { useStyle } from '@magento/venia-ui/lib/classify';
import defaultClasses from './trustBadges.module.css';

/**
 * TrustBadges - Display trust signals to increase conversion
 * Shows security, shipping, and return policy badges
 */
const TrustBadges = props => {
    const classes = useStyle(defaultClasses, props.classes);

    const badges = [
        {
            id: 'secure',
            icon: '🔒',
            title: 'Secure Checkout',
            description: '256-bit SSL Encryption'
        },
        {
            id: 'shipping',
            icon: '🚚',
            title: 'Free Shipping',
            description: 'On orders over $50'
        },
        {
            id: 'returns',
            icon: '↩️',
            title: '30-Day Returns',
            description: 'Easy & hassle-free'
        },
        {
            id: 'support',
            icon: '💬',
            title: '24/7 Support',
            description: 'Always here to help'
        }
    ];

    return (
        <div className={classes.root}>
            <div className={classes.container}>
                {badges.map(badge => (
                    <div key={badge.id} className={classes.badge}>
                        <span className={classes.icon} aria-hidden="true">
                            {badge.icon}
                        </span>
                        <div className={classes.content}>
                            <h3 className={classes.title}>{badge.title}</h3>
                            <p className={classes.description}>
                                {badge.description}
                            </p>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

TrustBadges.propTypes = {
    classes: shape({
        root: string,
        container: string,
        badge: string,
        icon: string,
        content: string,
        title: string,
        description: string
    })
};

export default TrustBadges;
