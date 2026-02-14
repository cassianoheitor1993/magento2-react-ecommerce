import { gql } from '@apollo/client';

export const GET_STORE_CONFIG_DATA = gql`
    query getStoreConfigData {
        storeConfig {
            store_code
            product_url_suffix
        }
    }
`;

export const PRODUCT_DETAILS_FRAGMENT = gql`
    fragment ProductDetailsFragment on ProductInterface {
        __typename
        categories {
            uid
            breadcrumbs {
                category_uid
                category_name
                category_url_path
            }
        }
        description {
            html
        }
        short_description {
            html
        }
        id
        uid
        media_gallery_entries {
            uid
            label
            position
            disabled
            file
        }
        meta_description
        name
        rating_summary
        review_count
        price {
            regularPrice {
                amount {
                    currency
                    value
                }
            }
        }
        price_range {
            maximum_price {
                final_price {
                    currency
                    value
                }
                discount {
                    amount_off
                }
            }
        }
        sku
        small_image {
            url
        }
        stock_status
        url_key
    }
`;

export const GET_PRODUCT_DETAIL_QUERY = gql`
    query getProductDetailForProductPage($urlKey: String!) {
        products(filter: { url_key: { eq: $urlKey } }) {
            items {
                id
                uid
                ...ProductDetailsFragment
            }
        }
    }
    ${PRODUCT_DETAILS_FRAGMENT}
`;

export default {
    getStoreConfigData: GET_STORE_CONFIG_DATA,
    getProductDetailQuery: GET_PRODUCT_DETAIL_QUERY
};
