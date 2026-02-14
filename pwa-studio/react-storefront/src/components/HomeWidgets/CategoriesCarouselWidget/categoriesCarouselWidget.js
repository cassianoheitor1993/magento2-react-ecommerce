import React, { useRef, useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import resourceUrl from '@magento/peregrine/lib/util/makeUrl';
import { useStyle } from '@magento/venia-ui/lib/classify';
import defaultClasses from './categoriesCarouselWidget.module.css';

const CategoriesCarouselWidget = props => {
    const { title = 'Shop by category', config = {} } = props;
    const classes = useStyle(defaultClasses, props.classes);

    const categories = Array.isArray(config.items) ? config.items : [];

    const trackRef = useRef(null);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    const updateScrollState = useCallback(() => {
        const el = trackRef.current;
        if (!el) return;
        const tolerance = 2;
        setCanScrollLeft(el.scrollLeft > tolerance);
        setCanScrollRight(el.scrollLeft + el.clientWidth < el.scrollWidth - tolerance);
    }, []);

    useEffect(() => {
        const el = trackRef.current;
        if (!el) return;

        updateScrollState();

        el.addEventListener('scroll', updateScrollState, { passive: true });
        const ro = new ResizeObserver(updateScrollState);
        ro.observe(el);

        return () => {
            el.removeEventListener('scroll', updateScrollState);
            ro.disconnect();
        };
    }, [updateScrollState, categories.length]);

    const scroll = direction => {
        const el = trackRef.current;
        if (!el) return;
        // Scroll by roughly 80% of the visible width
        const amount = el.clientWidth * 0.8;
        el.scrollBy({ left: direction === 'left' ? -amount : amount, behavior: 'smooth' });
    };

    return (
        <div className={classes.root}>
            <div className={classes.header}>
                <h3 className={classes.title}>{title}</h3>
                {config.cta_label && config.cta_url ? (
                    <Link className={classes.headerCta} to={resourceUrl(config.cta_url)}>
                        {config.cta_label}
                    </Link>
                ) : null}
            </div>

            <div className={classes.trackWrapper}>
                {canScrollLeft && (
                    <button
                        className={`${classes.scrollBtn} ${classes.scrollBtnLeft}`}
                        onClick={() => scroll('left')}
                        aria-label="Scroll left"
                        type="button"
                    >
                        &#8249;
                    </button>
                )}

                <div className={classes.track} ref={trackRef}>
                    {categories.map((category, index) => (
                        <Link
                            key={`${category.label || 'category'}-${index}`}
                            className={classes.item}
                            to={resourceUrl(category.url || '/')}>
                            {category.label || 'Category'}
                        </Link>
                    ))}
                </div>

                {canScrollRight && (
                    <button
                        className={`${classes.scrollBtn} ${classes.scrollBtnRight}`}
                        onClick={() => scroll('right')}
                        aria-label="Scroll right"
                        type="button"
                    >
                        &#8250;
                    </button>
                )}
            </div>
        </div>
    );
};

export default CategoriesCarouselWidget;
