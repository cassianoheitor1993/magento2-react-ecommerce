import React from 'react';
import { useStyle } from '@magento/venia-ui/lib/classify';
import defaultClasses from './testimonialsWidget.module.css';

const fallbackItems = [
    { quote: 'Excellent quality and fast delivery.', author_name: 'Maya R.' },
    { quote: 'My pets loved everything. Great support too.', author_name: 'Carlos D.' }
];

const TestimonialsWidget = props => {
    const { title = 'What customers say', config = {} } = props;
    const classes = useStyle(defaultClasses, props.classes);
    const items = Array.isArray(config.items) && config.items.length ? config.items : fallbackItems;

    return (
        <div className={classes.root}>
            <h3 className={classes.title}>{title}</h3>
            <div className={classes.grid}>
                {items.map((item, index) => (
                    <blockquote key={`${item.author_name || 'author'}-${index}`} className={classes.card}>
                        <p className={classes.quote}>“{item.quote}”</p>
                        <footer className={classes.author}>— {item.author_name || 'Customer'}</footer>
                    </blockquote>
                ))}
            </div>
        </div>
    );
};

export default TestimonialsWidget;
