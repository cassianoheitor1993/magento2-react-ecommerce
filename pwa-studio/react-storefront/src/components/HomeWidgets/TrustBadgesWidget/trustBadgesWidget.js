import React from 'react';
import { useStyle } from '@magento/venia-ui/lib/classify';
import defaultClasses from './trustBadgesWidget.module.css';

const defaultBadges = [
    { icon: '🔒', title: 'Secure Checkout', description: '256-bit SSL Encryption' },
    { icon: '🚚', title: 'Free Shipping', description: 'On orders over $50' },
    { icon: '↩️', title: '30-Day Returns', description: 'Easy and hassle-free' }
];

const TrustBadgesWidget = props => {
    const { title = 'Why shop with us', config = {} } = props;
    const classes = useStyle(defaultClasses, props.classes);
    const badges = Array.isArray(config.badges) && config.badges.length ? config.badges : defaultBadges;

    return (
        <div className={classes.root}>
            <h3 className={classes.title}>{title}</h3>
            <div className={classes.grid}>
                {badges.map((badge, index) => (
                    <article key={`${badge.title || 'badge'}-${index}`} className={classes.card}>
                        <span className={classes.icon}>{badge.icon || '✔️'}</span>
                        <div>
                            <p className={classes.cardTitle}>{badge.title}</p>
                            <p className={classes.description}>{badge.description}</p>
                        </div>
                    </article>
                ))}
            </div>
        </div>
    );
};

export default TrustBadgesWidget;
