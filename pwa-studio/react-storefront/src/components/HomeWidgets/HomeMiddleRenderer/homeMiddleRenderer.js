import React, { useMemo } from 'react';
import { useQuery } from '@apollo/client';
import { useStyle } from '@magento/venia-ui/lib/classify';
import { GET_HOMEPAGE_LAYOUT } from '../../../queries/getHomepageLayout.gql';
import widgetRegistry from '../registry';
import defaultClasses from './homeMiddleRenderer.module.css';

const parseConfig = configJson => {
    if (!configJson) {
        return {};
    }

    try {
        const parsed = JSON.parse(configJson);
        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
        return {};
    }
};

const HomeMiddleRenderer = props => {
    const classes = useStyle(defaultClasses, props.classes);

    const { data, loading } = useQuery(GET_HOMEPAGE_LAYOUT, {
        variables: { pageCode: 'home' },
        fetchPolicy: 'cache-and-network'
    });

    const widgets = useMemo(() => {
        const source = data?.homePageLayout?.middle_widgets || [];

        return source
            .filter(item => item && item.is_active)
            .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
    }, [data]);

    if (loading && !widgets.length) {
        return <div className={classes.loading}>Loading sections…</div>;
    }

    if (!widgets.length) {
        return null;
    }

    return (
        <div className={classes.root}>
            {widgets.map(widget => {
                const WidgetComponent = widgetRegistry[widget.widget_type];
                if (!WidgetComponent) {
                    return null;
                }

                return (
                    <section key={widget.widget_id || `${widget.widget_type}-${widget.sort_order}`} className={classes.section}>
                        <WidgetComponent
                            title={widget.title}
                            config={parseConfig(widget.config_json)}
                        />
                    </section>
                );
            })}
        </div>
    );
};

export default HomeMiddleRenderer;
