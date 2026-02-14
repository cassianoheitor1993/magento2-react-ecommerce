import React, { Fragment } from 'react';
import { shape, string } from 'prop-types';

import CMSPageShimmer from '@magento/venia-ui/lib/RootComponents/CMS/cms.shimmer';
import { useCmsPage } from '@magento/peregrine/lib/talons/Cms/useCmsPage';
import RichContent from '@magento/venia-ui/lib/components/RichContent';
import { Meta, StoreTitle } from '@magento/venia-ui/lib/components/Head';
import { useStyle } from '@magento/venia-ui/lib/classify';
import { toCamelCase } from '@magento/venia-ui/lib/util/toCamelCase';
import HomeRevamp from '../../components/HomeRevamp/homeRevamp';

import defaultClasses from '@magento/venia-ui/lib/RootComponents/CMS/cms.module.css';
import localClasses from './cms.module.css';

const CMSPage = props => {
    const { identifier } = props;

    const talonProps = useCmsPage({ identifier });
    const { cmsPage, shouldShowLoadingIndicator } = talonProps;
    const classes = useStyle(defaultClasses, localClasses, props.classes);

    if (shouldShowLoadingIndicator) {
        return <CMSPageShimmer classes={classes} />;
    }

    if (!cmsPage) {
        return null;
    }

    const {
        content_heading,
        title,
        meta_title,
        meta_description,
        page_layout,
        content,
        url_key
    } = cmsPage;

    const headingElement =
        content_heading !== '' ? (
            <h1 data-cy="Cms-contentHeading" className={classes.heading}>
                {content_heading}
            </h1>
        ) : null;

    const pageTitle = meta_title || title;
    const rootClassName = page_layout
        ? classes[`root_${toCamelCase(page_layout)}`]
        : classes.root;

    const isHomePage =
        ['home', 'home-page', 'index'].includes(identifier) ||
        ['home', 'home-page', 'index'].includes(url_key);

    const articleClassName = isHomePage
        ? `${rootClassName} ${classes.homeArticle}`
        : rootClassName;

    return (
        <Fragment>
            <StoreTitle>{pageTitle}</StoreTitle>
            <Meta name="title" content={pageTitle} />
            <Meta name="description" content={meta_description} />
            <article className={articleClassName}>
                {isHomePage ? (
                    <HomeRevamp />
                ) : (
                    <>
                        {headingElement}
                        <RichContent html={content} />
                    </>
                )}
            </article>
        </Fragment>
    );
};

CMSPage.propTypes = {
    identifier: string,
    classes: shape({
        root: string,
        heading: string,
        root_empty: string,
        root_1column: string,
        root_2columnsLeft: string,
        root_2columnsRight: string,
        root_3columns: string,
        root_cmsFullWidth: string,
        root_categoryFullWidth: string,
        root_productFullWidth: string
    })
};

export default CMSPage;
