import React from 'react';
import { Link } from 'react-router-dom';
import resourceUrl from '@magento/peregrine/lib/util/makeUrl';
import { useStyle } from '@magento/venia-ui/lib/classify';
import defaultClasses from './ctaWidget.module.css';

const CtaWidget = props => {
    const { title = 'Featured offer', config = {} } = props;
    const classes = useStyle(defaultClasses, props.classes);

    const content = config?.content || {};
    const cta = config?.cta || {};
    const behavior = config?.behavior || {};

    const headline = content.headline || config.headline || title;
    const eyebrow = content.eyebrow || '';
    const subheadline = content.subheadline || '';
    const description = content.body || config.description || '';
    const disclaimer = content.disclaimer || '';

    const primaryLabel = cta.label || config.primary_label;
    const secondaryLabel = cta.secondaryLabel || config.secondary_label;
    const primaryUrl = behavior?.primaryAction?.url || config.primary_url;
    const secondaryUrl = behavior?.secondaryAction?.url || config.secondary_url;

    return (
        <div className={classes.root}>
            {eyebrow ? <p className={classes.eyebrow}>{eyebrow}</p> : null}
            <h3 className={classes.headline}>{headline}</h3>
            {subheadline ? <p className={classes.subheadline}>{subheadline}</p> : null}
            {description ? <p className={classes.description}>{description}</p> : null}
            <div className={classes.actions}>
                {primaryLabel && primaryUrl ? (
                    <Link className={classes.primary} to={resourceUrl(primaryUrl)}>
                        {primaryLabel}
                    </Link>
                ) : null}
                {secondaryLabel && secondaryUrl ? (
                    <Link className={classes.secondary} to={resourceUrl(secondaryUrl)}>
                        {secondaryLabel}
                    </Link>
                ) : null}
            </div>
            {disclaimer ? <p className={classes.disclaimer}>{disclaimer}</p> : null}
        </div>
    );
};

export default CtaWidget;
