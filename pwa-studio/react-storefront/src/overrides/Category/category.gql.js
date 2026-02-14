import { gql } from '@apollo/client';

export const CATEGORY_FRAGMENT = gql`
    fragment LeisPetCategoryFragment on CategoryTree {
        uid
        name
        meta_title
        meta_keywords
        meta_description
        url_path
        url_key
        breadcrumbs {
            category_uid
            category_name
            category_url_path
        }
    }
`;

export const PRODUCTS_FRAGMENT = gql`
    fragment LeisPetProductsFragment on Products {
        aggregations {
            attribute_code
            label
            options {
                label
                value
                count
            }
        }
        items {
            id
            uid
            name
            media_gallery_entries {
                uid
                label
                position
                disabled
                file
            }
            media_gallery {
                url
                label
                position
                disabled
            }
            price_range {
                maximum_price {
                    final_price {
                        currency
                        value
                    }
                    regular_price {
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
            rating_summary
            __typename
            url_key
        }
        page_info {
            total_pages
        }
        total_count
    }
`;

export const GET_CATEGORY = gql`
    query GetCategories(
        $id: String!
        $pageSize: Int!
        $currentPage: Int!
        $filters: ProductAttributeFilterInput!
        $sort: ProductAttributeSortInput
    ) {
        categories(filters: { category_uid: { in: [$id] } }) {
            items {
                uid
                ...LeisPetCategoryFragment
            }
        }
        products(
            pageSize: $pageSize
            currentPage: $currentPage
            filter: $filters
            sort: $sort
        ) {
            ...LeisPetProductsFragment
        }
    }
    ${CATEGORY_FRAGMENT}
    ${PRODUCTS_FRAGMENT}
`;

export const GET_FILTER_INPUTS = gql`
    query GetFilterInputsForCategory {
        __type(name: "ProductAttributeFilterInput") {
            inputFields {
                name
                type {
                    name
                }
            }
        }
    }
`;

export const GET_PAGE_SIZE = gql`
    query getPageSize {
        # eslint-disable-next-line @graphql-eslint/require-id-when-available
        storeConfig {
            store_code
            grid_per_page
            category_url_suffix
        }
    }
`;

export default {
    getCategoryQuery: GET_CATEGORY,
    getFilterInputsQuery: GET_FILTER_INPUTS,
    queries: {
        getPageSize: GET_PAGE_SIZE
    }
};
