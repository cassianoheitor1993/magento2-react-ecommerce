import { gql } from '@apollo/client';

export const GET_BLOG_POSTS = gql`
    query getBlogPosts {
        blogPosts {
            items {
                post_id
                title
                slug
                excerpt
                content
                author
                tags
                created_at
                updated_at
            }
        }
    }
`;
