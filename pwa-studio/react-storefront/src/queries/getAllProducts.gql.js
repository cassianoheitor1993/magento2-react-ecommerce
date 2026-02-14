import { gql } from '@apollo/client';

export const GET_ALL_PRODUCTS = gql`
    query getAllProducts(
        $pageSize: Int!
        $currentPage: Int!
        $filters: ProductAttributeFilterInput!
    ) {
        products(
            pageSize: $pageSize
            currentPage: $currentPage
            filter: $filters
            sort: { name: ASC }
        ) {
            total_count
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
                uid
                sku
                name
                url_key
                url_suffix
                small_image {
                    url
                    label
                }
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
                    minimum_price {
                        final_price {
                            value
                            currency
                        }
                    }
                }
            }
            page_info {
                current_page
                total_pages
                page_size
            }
        }
    }
`;
