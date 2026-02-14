import { gql } from '@apollo/client';

export const GET_EXTENSION_CONTEXT = gql`
    query GetExtensionContext {
        storeConfig {
            store_code
        }
    }
`;
