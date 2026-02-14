import { gql } from '@apollo/client';

export const GET_NAVIGATION_CATEGORIES = gql`
    query getNavigationCategories {
        storeConfig {
            store_code
            category_url_suffix
        }
        categoryList(filters: {}) {
            uid
            name
            url_key
            url_path
            include_in_menu
            children {
                uid
                name
                url_key
                url_path
                include_in_menu
                children {
                    uid
                    name
                    url_key
                    url_path
                    include_in_menu
                    children {
                        uid
                        name
                        url_key
                        url_path
                        include_in_menu
                    }
                }
            }
        }
    }
`;
