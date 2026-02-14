import React, { Fragment, Suspense, useCallback, useState } from 'react';
import { shape, string } from 'prop-types';
import { Link, NavLink, Route, useHistory, useLocation } from 'react-router-dom';
import { useQuery } from '@apollo/client';

import Logo from '@magento/venia-ui/lib/components/Logo';
import AccountTrigger from '@magento/venia-ui/lib/components/Header/accountTrigger';
import CartTrigger from '@magento/venia-ui/lib/components/Header/cartTrigger';
import NavTrigger from '@magento/venia-ui/lib/components/Header/navTrigger';
import SearchTrigger from '@magento/venia-ui/lib/components/Header/searchTrigger';
import OnlineIndicator from '@magento/venia-ui/lib/components/Header/onlineIndicator';
import { useHeader } from '@magento/peregrine/lib/talons/Header/useHeader';
import resourceUrl from '@magento/peregrine/lib/util/makeUrl';

import { useStyle } from '@magento/venia-ui/lib/classify';
import defaultClasses from '@magento/venia-ui/lib/components/Header/header.module.css';
import localClasses from './header.local.module.css';
import StoreSwitcher from '@magento/venia-ui/lib/components/Header/storeSwitcher';
import CurrencySwitcher from '@magento/venia-ui/lib/components/Header/currencySwitcher';
import PageLoadingIndicator from '@magento/venia-ui/lib/components/PageLoadingIndicator';
import { useIntl } from 'react-intl';
import AutocompleteSearch from '../AutocompleteSearch';
import SitewideJsonLd from '../Seo/sitewideJsonLd';
import {
    isStorefrontRevampEnabled,
    shouldRenderJsonLd
} from '../../utils/featureFlags';
import { GET_NAVIGATION_CATEGORIES } from '../../queries/getNavigationCategories.gql';

const SearchBar = React.lazy(() => import('@magento/venia-ui/lib/components/SearchBar'));

const Header = props => {
    const {
        handleSearchTriggerClick,
        hasBeenOffline,
        isOnline,
        isSearchOpen,
        searchRef,
        searchTriggerRef
    } = useHeader();
    const history = useHistory();
    const location = useLocation();
    const [searchValue, setSearchValue] = useState('');
    const { data: navigationCategoriesData } = useQuery(GET_NAVIGATION_CATEGORIES, {
        fetchPolicy: 'cache-and-network'
    });

    const classes = useStyle(defaultClasses, localClasses, props.classes);
    const rootClass = isSearchOpen ? classes.open : classes.closed;

    const handleSearchValueChange = useCallback(event => {
        setSearchValue(event?.target?.value || '');
    }, []);

    const handleSearchSubmit = useCallback(
        event => {
            if (event?.preventDefault) {
                event.preventDefault();
            }

            const query = searchValue.trim();

            if (!query) {
                return;
            }

            history.push(`/search.html?query=${encodeURIComponent(query)}`);
            setSearchValue('');
            handleSearchTriggerClick();
        },
        [history, searchValue, handleSearchTriggerClick]
    );

    const searchBar = isSearchOpen ? (
        <div className={classes.searchFallback} ref={searchRef}>
            {isStorefrontRevampEnabled ? (
                <AutocompleteSearch
                    value={searchValue}
                    onChange={handleSearchValueChange}
                    onSubmit={handleSearchSubmit}
                    isOpen={isSearchOpen}
                />
            ) : (
                <Suspense fallback={null}>
                    <Route>
                        <SearchBar isOpen={isSearchOpen} ref={searchRef} />
                    </Route>
                </Suspense>
            )}
        </div>
    ) : null;

    const { formatMessage } = useIntl();
    const title = formatMessage({ id: 'logo.title', defaultMessage: 'Petshop' });
    const homeLabel = formatMessage({
        id: 'navigation.home',
        defaultMessage: 'Home'
    });

    const categorySuffix =
        navigationCategoriesData?.storeConfig?.category_url_suffix || '.html';
    const navigationItemsRaw = (navigationCategoriesData?.categoryList || []).filter(
        item => item?.include_in_menu !== 0 && item?.url_path
    );

    const navigationItems = navigationItemsRaw.filter(item =>
        !navigationItemsRaw.some(
            candidateParent =>
                candidateParent.uid !== item.uid &&
                item.url_path.indexOf(`${candidateParent.url_path}/`) === 0
        )
    );

    const getVisibleChildren = node =>
        (node?.children || []).filter(
            child => child?.include_in_menu !== 0 && child?.url_path
        );

    const isCategoryPathActive = urlPath => {
        const basePath = `/${urlPath}`;

        return (
            location.pathname === resourceUrl(basePath) ||
            location.pathname === resourceUrl(`${basePath}${categorySuffix}`)
        );
    };

    const renderDropdownItems = (items, level = 1) =>
        items.map(child => {
            const grandChildren = getVisibleChildren(child);
            const levelClass =
                level === 1
                    ? classes.dropdownLink
                    : level === 2
                      ? `${classes.dropdownLink} ${classes.dropdownSubLink}`
                      : `${classes.dropdownLink} ${classes.dropdownSubSubLink}`;

            return (
                <React.Fragment key={child.uid}>
                    {grandChildren.length ? (
                        <details className={classes.dropdownCollapsible}>
                            <summary className={`${levelClass} ${classes.dropdownSummary}`}>
                                {child.name}
                            </summary>
                            <div className={classes.dropdownGroup}>
                                <NavLink
                                    to={resourceUrl(`/${child.url_path}${categorySuffix}`)}
                                    className={`${classes.dropdownLink} ${classes.dropdownSubLink}`}
                                >
                                    {`View all ${child.name}`}
                                </NavLink>
                                {renderDropdownItems(grandChildren, level + 1)}
                            </div>
                        </details>
                    ) : (
                        <NavLink
                            to={resourceUrl(`/${child.url_path}${categorySuffix}`)}
                            className={levelClass}
                        >
                            {child.name}
                        </NavLink>
                    )}
                </React.Fragment>
            );
        });

    return (
        <Fragment>
            {shouldRenderJsonLd ? <SitewideJsonLd /> : null}
            <div className={classes.switchersContainer}>
                <div className={classes.switchers} data-cy="Header-switchers">
                    <StoreSwitcher />
                    <CurrencySwitcher />
                </div>
            </div>
            <header className={rootClass} data-cy="Header-root">
                <div className={classes.toolbar}>
                    <div className={classes.primaryActions}>
                        <NavTrigger />
                    </div>

                    <Link
                        aria-label={title}
                        to={resourceUrl('/')}
                        className={classes.logoContainer}
                        data-cy="Header-logoContainer"
                    >
                        <Logo classes={{ logo: classes.logo }} />
                    </Link>
                    <nav className={classes.mainNav} aria-label="Main navigation">
                        <NavLink
                            exact
                            to={resourceUrl('/')}
                            className={classes.navLink}
                            activeClassName={classes.navLinkActive}
                        >
                            {homeLabel}
                        </NavLink>
                        {navigationItems.map(item => {
                            const children = (item.children || []).filter(
                                child => child?.include_in_menu !== 0 && child?.url_path
                            );
                            const parentActive = isCategoryPathActive(item.url_path);
                            const childrenActive = children.some(child =>
                                isCategoryPathActive(child.url_path)
                            );
                            const groupActive = parentActive || childrenActive;

                            if (children.length) {
                                return (
                                    <div
                                        key={item.uid}
                                        className={`${classes.navGroup} ${
                                            groupActive ? classes.navGroupActive : ''
                                        }`}
                                    >
                                        <NavLink
                                            to={resourceUrl(
                                                `/${item.url_path}${categorySuffix}`
                                            )}
                                            isActive={() => parentActive}
                                            className={classes.navGroupTrigger}
                                            activeClassName={classes.navLinkActive}
                                        >
                                            {item.name}
                                        </NavLink>
                                        <div className={classes.dropdown}>
                                            {renderDropdownItems(children)}
                                        </div>
                                    </div>
                                );
                            }

                            return (
                                <NavLink
                                    key={item.uid}
                                    to={resourceUrl(`/${item.url_path}${categorySuffix}`)}
                                    isActive={() => isCategoryPathActive(item.url_path)}
                                    className={classes.navLink}
                                    activeClassName={classes.navLinkActive}
                                >
                                    {item.name}
                                </NavLink>
                            );
                        })}
                    </nav>
                    <div className={classes.secondaryActions}>
                        <SearchTrigger
                            onClick={handleSearchTriggerClick}
                            ref={searchTriggerRef}
                        />
                        <AccountTrigger />
                        <CartTrigger />
                    </div>
                </div>
                {searchBar}
                <PageLoadingIndicator absolute />
            </header>
            <OnlineIndicator
                hasBeenOffline={hasBeenOffline}
                isOnline={isOnline}
            />
        </Fragment>
    );
};

Header.propTypes = {
    classes: shape({
        closed: string,
        logo: string,
        open: string,
        primaryActions: string,
        secondaryActions: string,
        toolbar: string,
        switchers: string,
        switchersContainer: string
    })
};

export default Header;
