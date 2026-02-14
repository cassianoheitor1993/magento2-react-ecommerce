import React, { useState } from 'react';
import { useStyle } from '@magento/venia-ui/lib/classify';
import defaultClasses from './newsletterWidget.module.css';

const NewsletterWidget = props => {
    const { title = 'Newsletter', config = {} } = props;
    const classes = useStyle(defaultClasses, props.classes);
    const [email, setEmail] = useState('');

    return (
        <div className={classes.root}>
            <h3 className={classes.title}>{config.headline || title}</h3>
            {config.description ? <p className={classes.description}>{config.description}</p> : null}
            <form
                className={classes.form}
                onSubmit={event => {
                    event.preventDefault();
                }}
            >
                <input
                    className={classes.input}
                    type="email"
                    required
                    value={email}
                    onChange={event => setEmail(event.target.value)}
                    placeholder={config.email_placeholder || 'you@email.com'}
                />
                <button className={classes.button} type="submit">
                    {config.button_label || 'Subscribe'}
                </button>
            </form>
            {config.disclaimer_text ? <p className={classes.disclaimer}>{config.disclaimer_text}</p> : null}
        </div>
    );
};

export default NewsletterWidget;
