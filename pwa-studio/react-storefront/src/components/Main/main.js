import React from 'react';
import { bool, shape, string } from 'prop-types';
import { useScrollLock } from '@magento/peregrine';

import { useStyle } from '@magento/venia-ui/lib/classify';
import Footer from '@magento/venia-ui/lib/components/Footer/footer';
import Header from '../Header/header';
import AnnouncementBar from '../AnnouncementBar/announcementBar';
import defaultClasses from '@magento/venia-ui/lib/components/Main/main.module.css';

const isRenderableComponent = Component =>
    typeof Component === 'function' ||
    (typeof Component === 'object' && Component !== null);

const Main = props => {
    const { children, isMasked } = props;
    const classes = useStyle(defaultClasses, props.classes);

    const rootClass = isMasked ? classes.root_masked : classes.root;
    const pageClass = isMasked ? classes.page_masked : classes.page;

    useScrollLock(isMasked);

    const ResolvedAnnouncementBar = isRenderableComponent(AnnouncementBar)
        ? AnnouncementBar
        : null;
    const ResolvedHeader = isRenderableComponent(Header) ? Header : null;
    const ResolvedFooter = isRenderableComponent(Footer) ? Footer : null;

    if (!ResolvedAnnouncementBar) {
        console.warn('[Main] AnnouncementBar import is undefined or invalid.');
    }

    if (!ResolvedHeader) {
        console.warn('[Main] Header import is undefined or invalid.');
    }

    if (!ResolvedFooter) {
        console.warn('[Main] Footer import is undefined or invalid.');
    }

    return (
        <main className={rootClass}>
            {ResolvedAnnouncementBar ? <ResolvedAnnouncementBar /> : null}
            {ResolvedHeader ? <ResolvedHeader /> : null}
            <div className={pageClass}>{children}</div>
            {ResolvedFooter ? <ResolvedFooter /> : null}
        </main>
    );
};

export default Main;

Main.propTypes = {
    classes: shape({
        page: string,
        page_masked: string,
        root: string,
        root_masked: string
    }),
    isMasked: bool
};
