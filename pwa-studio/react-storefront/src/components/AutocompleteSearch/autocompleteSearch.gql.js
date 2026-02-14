import { gql } from '@apollo/client';

export const PRODUCT_SEARCH_QUERY = gql`
    query ProductSearch($search: String!, $pageSize: Int = 5) {
        products(search: $search, pageSize: $pageSize) {
            items {
                id
                uid
                name
                sku
                url_key
                small_image {
                    url
                    label
                }
                price_range {
                    minimum_price {
                        final_price {
                            value
                            currency
                        }
                        regular_price {
                            value
                            currency
                        }
                    }
                }
                rating_summary
                review_count
                stock_status
            }
            total_count
        }
    }
`;

export default {
    queries: {
        getProductSearchQuery: PRODUCT_SEARCH_QUERY
    }
};
