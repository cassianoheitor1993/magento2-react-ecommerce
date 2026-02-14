import React from 'react';
import { useStyle } from '@magento/venia-ui/lib/classify';
import defaultClasses from './announcementBar.module.css';

const AnnouncementBar = props => {
    const classes = useStyle(defaultClasses, props.classes);

    return (
        <div className={classes.root} role="status" aria-live="polite">
            <div className={classes.content}>
                <span className={classes.badge}>New</span>
                <span>
                    Free shipping over $50 • 30-day returns • Secure checkout
                </span>
            </div>
        </div>
    );
};

export default AnnouncementBar;
