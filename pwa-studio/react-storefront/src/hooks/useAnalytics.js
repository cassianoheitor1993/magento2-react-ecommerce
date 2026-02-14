import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { trackPageView, trackProductImpression } from '../utils/analytics';

/**
 * Hook to automatically track page views on route changes
 */
export const usePageTracking = () => {
    const location = useLocation();
    
    useEffect(() => {
        trackPageView(location.pathname + location.search, document.title);
    }, [location]);
};

/**
 * Hook for tracking product impressions when products come into view
 */
export const useProductImpressionTracking = (products, listName) => {
    useEffect(() => {
        if (products && products.length > 0) {
            // Use Intersection Observer for accurate impression tracking
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const productIndex = parseInt(
                                entry.target.getAttribute('data-product-index'),
                                10
                            );
                            if (!isNaN(productIndex)) {
                                // Track individual product impression
                                const product = products[productIndex];
                                if (product) {
                                    trackProductImpression([product], listName);
                                }
                            }
                        }
                    });
                },
                { threshold: 0.5 } // Track when 50% visible
            );
            
            // Observe product elements
            const productElements = document.querySelectorAll('[data-product-index]');
            productElements.forEach(el => observer.observe(el));
            
            return () => observer.disconnect();
        }
    }, [products, listName]);
};

export default {
    usePageTracking,
    useProductImpressionTracking
};
