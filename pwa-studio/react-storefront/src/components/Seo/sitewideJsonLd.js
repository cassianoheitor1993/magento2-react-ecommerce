import React, { useMemo } from 'react';

const SitewideJsonLd = () => {
    const schemas = useMemo(() => {
        const origin =
            typeof window !== 'undefined'
                ? window.location.origin
                : process.env.MAGENTO_BACKEND_URL || '';

        if (!origin) {
            return [];
        }

        const organization = {
            '@context': 'https://schema.org',
            '@type': 'Organization',
            name: typeof STORE_NAME !== 'undefined' ? STORE_NAME : 'Store',
            url: origin,
            logo: `${origin}/favicon.ico`
        };

        const website = {
            '@context': 'https://schema.org',
            '@type': 'WebSite',
            name: typeof STORE_NAME !== 'undefined' ? STORE_NAME : 'Store',
            url: origin,
            potentialAction: {
                '@type': 'SearchAction',
                target: `${origin}/search.html?query={search_term_string}`,
                'query-input': 'required name=search_term_string'
            }
        };

        return [organization, website];
    }, []);

    return (
        <>
            {schemas.map((schema, index) => (
                <script
                    key={`sitewide-jsonld-${index}`}
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{
                        __html: JSON.stringify(schema)
                    }}
                />
            ))}
        </>
    );
};

export default SitewideJsonLd;
