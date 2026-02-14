/* eslint-disable */
/**
 * Custom interceptors for the project.
 *
 * This project has a section in its package.json:
 *    "pwa-studio": {
 *        "targets": {
 *            "intercept": "./local-intercept.js"
 *        }
 *    }
 *
 * This instructs Buildpack to invoke this file during the intercept phase,
 * as the very last intercept to run.
 *
 * A project can intercept targets from any of its dependencies. In a project
 * with many customizations, this function would tap those targets and add
 * or modify functionality from its dependencies.
 *
 * NOTE: Webpack aliases do NOT work for relative imports inside node_modules.
 * We use NormalModuleReplacementPlugin instead, which intercepts module
 * resolution at a lower level and can replace modules resolved via relative paths.
 */

const path = require('path');
const webpack = require('webpack');

function localIntercept(targets) {
    const buildpack = targets.of('@magento/pwa-buildpack');
    const veniaUI = targets.of('@magento/venia-ui');

    // Add custom blog route
    veniaUI.routes.tap(routes => {
        routes.push({
            name: 'BlogPost',
            pattern: '/blog/:slug',
            exact: true,
            path: require.resolve('./src/components/BlogPage')
        });
        routes.push({
            name: 'Blog',
            pattern: '/blog',
            exact: true,
            path: require.resolve('./src/components/BlogPage')
        });
        routes.push({
            name: 'BlogHtml',
            pattern: '/blog.html',
            exact: true,
            path: require.resolve('./src/components/BlogPage')
        });
        routes.push({
            name: 'KpiMonitor',
            pattern: '/kpi-monitor',
            exact: true,
            path: require.resolve('./src/components/KpiMonitorPage')
        });
        return routes;
    });

    // Override header to add custom navigation
    buildpack.webpackCompiler.tap(compiler => {
        const moduleOverrides = {
            '@magento/venia-ui/lib/components/Header/header.js': path.resolve(
                __dirname,
                'src/components/Header/header.js'
            ),
            '@magento/venia-ui/lib/components/Navigation/navigation.js': path.resolve(
                __dirname,
                'src/overrides/Navigation/navigation.js'
            ),
            '@magento/venia-ui/lib/components/Main/main.js': path.resolve(
                __dirname,
                'src/components/Main/main.js'
            ),
            '@magento/venia-ui/lib/RootComponents/CMS/cms.js': path.resolve(
                __dirname,
                'src/overrides/CMS/cms.js'
            ),
            // Adobe Commerce → Open Source compatibility fixes
            '@magento/experience-platform-connector/src/queries/getExtensionContext.js': path.resolve(
                __dirname,
                'src/overrides/experience-platform-connector/getExtensionContext.js'
            ),
            '@magento/experience-platform-connector/src/main.js': path.resolve(
                __dirname,
                'src/overrides/experience-platform-connector/main.js'
            ),
            '@magento/venia-ui/lib/RootComponents/Category/category.gql.js': path.resolve(
                __dirname,
                'src/overrides/Category/category.gql.js'
            ),
            '@magento/venia-ui/lib/RootComponents/Category/category.js': path.resolve(
                __dirname,
                'src/overrides/Category/category.js'
            ),
            '@magento/venia-ui/lib/RootComponents/Product/product.js': path.resolve(
                __dirname,
                'src/overrides/Product/product.js'
            ),
            '@magento/venia-ui/lib/components/Gallery/item.js': path.resolve(
                __dirname,
                'src/overrides/Gallery/item.js'
            ),
            '@magento/peregrine/lib/talons/RootComponents/Product/useProduct.js': path.resolve(
                __dirname,
                'src/overrides/Product/useProduct.js'
            ),
            '@magento/peregrine/lib/talons/MegaMenu/useMegaMenuItem.js': path.resolve(
                __dirname,
                'src/overrides/MegaMenu/useMegaMenuItem.js'
            ),
            '@magento/peregrine/lib/talons/Header/cartTriggerFragments.gql.js': path.resolve(
                __dirname,
                'src/overrides/Header/cartTriggerFragments.gql.js'
            )
        };

        Object.entries(moduleOverrides).forEach(([originalPath, replacementPath]) => {
            new webpack.NormalModuleReplacementPlugin(
                new RegExp(
                    originalPath.replace(/[/\\]/g, '[/\\\\]').replace(/\./g, '\\.')
                ),
                replacementPath
            ).apply(compiler);
        });

        console.log('[LocalIntercept] Module overrides:', Object.keys(moduleOverrides));
    });

    // No custom overrides currently applied
    // buildpack.webpackCompiler.tap(compiler => {
    //     // Add module overrides here if needed
    // });
}

module.exports = localIntercept;
