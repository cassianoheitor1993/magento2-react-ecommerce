import React, { useEffect, useMemo, useState } from 'react';
import { useStyle } from '@magento/venia-ui/lib/classify';
import defaultClasses from './kpiMonitorPage.module.css';

const thresholds = {
    searchCtrMin: 0.1,
    zeroResultMax: 0.3,
    addToCartRateMin: 0.05,
    checkoutToOrderMin: 0.3
};

const initialCounters = {
    search_request: 0,
    search_response: 0,
    search_result_click: 0,
    product_page_view: 0,
    category_page_view: 0,
    add_to_cart: 0,
    checkout_page_view: 0,
    place_order_click: 0,
    order_confirmation_page_view: 0
};

const KpiMonitorPage = props => {
    const classes = useStyle(defaultClasses, props.classes);
    const [counters, setCounters] = useState(initialCounters);
    const [zeroResultCount, setZeroResultCount] = useState(0);
    const [latestEvents, setLatestEvents] = useState([]);

    useEffect(() => {
        const onProbe = event => {
            const detail = event?.detail;
            const eventName = detail?.name;
            const payload = detail?.payload || {};

            if (!eventName || !(eventName in initialCounters)) {
                return;
            }

            setCounters(prev => ({
                ...prev,
                [eventName]: prev[eventName] + 1
            }));

            if (eventName === 'search_response' && Number(payload?.totalCount || 0) === 0) {
                setZeroResultCount(prev => prev + 1);
            }

            setLatestEvents(prev => {
                const next = [
                    {
                        name: eventName,
                        at: new Date(detail.timestamp || Date.now()).toLocaleTimeString(),
                        payload
                    },
                    ...prev
                ];

                return next.slice(0, 12);
            });
        };

        window.addEventListener('storefront-kpi-probe', onProbe);
        return () => window.removeEventListener('storefront-kpi-probe', onProbe);
    }, []);

    const metrics = useMemo(() => {
        const searchCtr =
            counters.search_response > 0
                ? counters.search_result_click / counters.search_response
                : 0;

        const zeroResultRate =
            counters.search_response > 0 ? zeroResultCount / counters.search_response : 0;

        const addToCartRate =
            counters.product_page_view > 0
                ? counters.add_to_cart / counters.product_page_view
                : 0;

        const checkoutToOrderRate =
            counters.checkout_page_view > 0
                ? counters.order_confirmation_page_view / counters.checkout_page_view
                : 0;

        return {
            searchCtr,
            zeroResultRate,
            addToCartRate,
            checkoutToOrderRate
        };
    }, [counters, zeroResultCount]);

    const checkStatus = (value, minOrMax, type = 'min') => {
        if (type === 'min') {
            return value >= minOrMax ? 'good' : 'warn';
        }

        return value <= minOrMax ? 'good' : 'warn';
    };

    const reset = () => {
        setCounters(initialCounters);
        setZeroResultCount(0);
        setLatestEvents([]);
    };

    return (
        <main className={classes.root}>
            <header className={classes.header}>
                <h1 className={classes.title}>Storefront KPI Monitor</h1>
                <button className={classes.resetButton} onClick={reset}>
                    Reset Counters
                </button>
            </header>

            <section className={classes.grid}>
                <article className={classes.card}>
                    <h2>Search CTR</h2>
                    <p className={classes.value}>{(metrics.searchCtr * 100).toFixed(1)}%</p>
                    <p
                        className={`${classes.badge} ${
                            classes[checkStatus(metrics.searchCtr, thresholds.searchCtrMin)]
                        }`}
                    >
                        Target ≥ {(thresholds.searchCtrMin * 100).toFixed(0)}%
                    </p>
                </article>

                <article className={classes.card}>
                    <h2>Zero-result Rate</h2>
                    <p className={classes.value}>{(metrics.zeroResultRate * 100).toFixed(1)}%</p>
                    <p
                        className={`${classes.badge} ${
                            classes[
                                checkStatus(metrics.zeroResultRate, thresholds.zeroResultMax, 'max')
                            ]
                        }`}
                    >
                        Target ≤ {(thresholds.zeroResultMax * 100).toFixed(0)}%
                    </p>
                </article>

                <article className={classes.card}>
                    <h2>PDP → Add to Cart</h2>
                    <p className={classes.value}>{(metrics.addToCartRate * 100).toFixed(1)}%</p>
                    <p
                        className={`${classes.badge} ${
                            classes[checkStatus(metrics.addToCartRate, thresholds.addToCartRateMin)]
                        }`}
                    >
                        Target ≥ {(thresholds.addToCartRateMin * 100).toFixed(0)}%
                    </p>
                </article>

                <article className={classes.card}>
                    <h2>Checkout → Order</h2>
                    <p className={classes.value}>{(metrics.checkoutToOrderRate * 100).toFixed(1)}%</p>
                    <p
                        className={`${classes.badge} ${
                            classes[
                                checkStatus(
                                    metrics.checkoutToOrderRate,
                                    thresholds.checkoutToOrderMin
                                )
                            ]
                        }`}
                    >
                        Target ≥ {(thresholds.checkoutToOrderMin * 100).toFixed(0)}%
                    </p>
                </article>
            </section>

            <section className={classes.split}>
                <article className={classes.panel}>
                    <h3>Event Counters</h3>
                    <ul className={classes.counterList}>
                        {Object.entries(counters).map(([name, count]) => (
                            <li key={name}>
                                <span>{name}</span>
                                <strong>{count}</strong>
                            </li>
                        ))}
                    </ul>
                </article>

                <article className={classes.panel}>
                    <h3>Latest Events</h3>
                    <ul className={classes.eventList}>
                        {latestEvents.length === 0 ? (
                            <li>No KPI probes received yet.</li>
                        ) : (
                            latestEvents.map((item, index) => (
                                <li key={`${item.name}-${index}`}>
                                    <div>
                                        <strong>{item.name}</strong> <span>({item.at})</span>
                                    </div>
                                    <pre>{JSON.stringify(item.payload, null, 2)}</pre>
                                </li>
                            ))
                        )}
                    </ul>
                </article>
            </section>
        </main>
    );
};

export default KpiMonitorPage;
