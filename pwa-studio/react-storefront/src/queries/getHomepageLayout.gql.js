import { gql } from '@apollo/client';

export const GET_HOMEPAGE_LAYOUT = gql`
    query getHomePageLayout($pageCode: String!) {
        homePageLayout(pageCode: $pageCode) {
            page_code
            static_sections {
                code
                is_enabled
                is_orderable
            }
            middle_widgets {
                widget_id
                widget_type
                title
                page_code
                placement
                is_active
                sort_order
                config_json
            }
        }
    }
`;
