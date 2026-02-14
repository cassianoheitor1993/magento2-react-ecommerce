import React, { useEffect, useState } from 'react';
import { useHistory } from 'react-router-dom';
import { useStyle } from '@magento/venia-ui/lib/classify';
import HomeMiddleRenderer from '../HomeWidgets/HomeMiddleRenderer';
import defaultClasses from './homeRevamp.module.css';

const slides = [
    {
        id: 'slide-1',
        eyebrow: 'Limited Time',
        title: 'Save up to 30% on essentials',
        subtitle: 'Fast shipping and curated products for your pet lifestyle.',
        cta: 'Shop Deals',
        target: '/search.html?query=deal'
    },
    {
        id: 'slide-2',
        eyebrow: 'Best Sellers',
        title: 'Top-rated picks customers love',
        subtitle: 'Discover products with excellent reviews and repeat purchases.',
        cta: 'Explore Best Sellers',
        target: '/search.html?query=best%20seller'
    },
    {
        id: 'slide-3',
        eyebrow: 'New Arrivals',
        title: 'Fresh products added weekly',
        subtitle: 'Stay ahead with new collections and exclusive launches.',
        cta: 'See New Arrivals',
        target: '/search.html?query=new'
    }
];

const HomeRevamp = props => {
    const classes = useStyle(defaultClasses, props.classes);
    const history = useHistory();
    const [activeSlide, setActiveSlide] = useState(0);
    useEffect(() => {
        const timer = setInterval(() => {
            setActiveSlide(prev => (prev + 1) % slides.length);
        }, 5000);

        return () => clearInterval(timer);
    }, []);

    const goTo = target => {
        history.push(target);
    };

    return (
        <section className={classes.root}>
            <div className={classes.hero}>
                {slides.map((slide, index) => {
                    const isActive = index === activeSlide;

                    return (
                        <article
                            key={slide.id}
                            className={`${classes.slide} ${
                                isActive ? classes.slideActive : ''
                            }`}
                            aria-hidden={!isActive}
                        >
                            <span className={classes.eyebrow}>{slide.eyebrow}</span>
                            <h2 className={classes.title}>{slide.title}</h2>
                            <p className={classes.subtitle}>{slide.subtitle}</p>
                            <button
                                className={classes.primaryCta}
                                onClick={() => goTo(slide.target)}
                            >
                                {slide.cta}
                            </button>
                        </article>
                    );
                })}

                <div className={classes.dots}>
                    {slides.map((slide, index) => (
                        <button
                            key={slide.id}
                            className={`${classes.dot} ${
                                index === activeSlide ? classes.dotActive : ''
                            }`}
                            onClick={() => setActiveSlide(index)}
                            aria-label={`Go to slide ${index + 1}`}
                        />
                    ))}
                </div>
            </div>

            <div className={classes.widgetsContainer}>
                <HomeMiddleRenderer />
            </div>
        </section>
    );
};

export default HomeRevamp;
