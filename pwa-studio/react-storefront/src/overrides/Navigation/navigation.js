import React, { Suspense } from 'react';
import { NavLink } from 'react-router-dom';
import { shape, string } from 'prop-types';
import { useQuery } from '@apollo/client';
import { useNavigation } from '@magento/peregrine/lib/talons/Navigation/useNavigation';

import resourceUrl from '@magento/peregrine/lib/util/makeUrl';
import { useStyle } from '@magento/venia-ui/lib/classify';
import AuthBar from '@magento/venia-ui/lib/components/AuthBar';
import CurrencySwitcher from '@magento/venia-ui/lib/components/Header/currencySwitcher';
import StoreSwitcher from '@magento/venia-ui/lib/components/Header/storeSwitcher';
import LoadingIndicator from '@magento/venia-ui/lib/components/LoadingIndicator';
import NavHeader from '@magento/venia-ui/lib/components/Navigation/navHeader';
import defaultClasses from '@magento/venia-ui/lib/components/Navigation/navigation.module.css';
import localClasses from './navigation.local.module.css';
import { FocusScope } from 'react-aria';
import { Portal } from '@magento/venia-ui/lib/components/Portal';
import { GET_NAVIGATION_CATEGORIES } from '../../queries/getNavigationCategories.gql';

const AuthModal = React.lazy(() =>
    import('@magento/venia-ui/lib/components/AuthModal')
);

const Navigation = props => {
    const {
        handleBack,
        handleClose,
        hasModal,
        isOpen,
        isTopLevel,
        showCreateAccount,
        showForgotPassword,
        showMainMenu,
        showMyAccount,
        showSignIn,
        view
    } = useNavigation();
    const { data: navigationCategoriesData } = useQuery(GET_NAVIGATION_CATEGORIES, {
        fetchPolicy: 'cache-and-network'
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

    const renderNavItems = (items, level = 0) =>
        items.map(item => {
            const basePath = `/${item.url_path}`;
            const children = getVisibleChildren(item);
            const levelClass =
                level === 0
                    ? classes.homeItemLink
                    : level === 1
                      ? `${classes.homeItemLink} ${classes.homeSubItemLink || ''}`
                      : `${classes.homeItemLink} ${classes.homeSubSubItemLink || ''}`;

            return (
                <React.Fragment key={item.uid}>
                    {children.length ? (
                        <details className={classes.homeCollapsible}>
                            <summary className={`${levelClass} ${classes.homeCollapsibleSummary}`}>
                                {item.name}
                            </summary>
                            <div className={classes.homeCollapsibleContent}>
                                <NavLink
                                    to={resourceUrl(`${basePath}${categorySuffix}`)}
                                    isActive={(match, location) =>
                                        location.pathname === resourceUrl(basePath) ||
                                        location.pathname ===
                                            resourceUrl(`${basePath}${categorySuffix}`)
                                    }
                                    className={`${classes.homeItemLink} ${classes.homeSubItemLink || ''}`}
                                    activeClassName={classes.homeItemLinkActive}
                                    onClick={handleClose}
                                >
                                    {`View all ${item.name}`}
                                </NavLink>
                                {renderNavItems(children, level + 1)}
                            </div>
                        </details>
                    ) : (
                        <NavLink
                            to={resourceUrl(`${basePath}${categorySuffix}`)}
                            isActive={(match, location) =>
                                location.pathname === resourceUrl(basePath) ||
                                location.pathname ===
                                    resourceUrl(`${basePath}${categorySuffix}`)
                            }
                            className={levelClass}
                            activeClassName={classes.homeItemLinkActive}
                            onClick={handleClose}
                        >
                            {item.name}
                        </NavLink>
                    )}
                </React.Fragment>
            );
        });

    const classes = useStyle(defaultClasses, localClasses, props.classes);
    const rootClassName = isOpen ? classes.root_open : classes.root;
    const modalClassName = hasModal ? classes.modal_open : classes.modal;
    const bodyClassName = hasModal ? classes.body_masked : classes.body;
    const authModal = hasModal ? (
        <Suspense fallback={<LoadingIndicator />}>
            <AuthModal
                closeDrawer={handleClose}
                showCreateAccount={showCreateAccount}
                showForgotPassword={showForgotPassword}
                showMainMenu={showMainMenu}
                showMyAccount={showMyAccount}
                showSignIn={showSignIn}
                view={view}
            />
        </Suspense>
    ) : null;

    return (
        <Portal>
            <FocusScope contain={isOpen} restoreFocus autoFocus>
                <aside className={rootClassName}>
                    <header className={classes.header}>
                        <NavHeader
                            isTopLevel={isTopLevel}
                            onBack={handleBack}
                            view={view}
                        />
                    </header>
                    <div className={bodyClassName}>
                        <div className={classes.homeItemWrapper}>
                            <NavLink
                                exact
                                to={resourceUrl('/')}
                                className={classes.homeItemLink}
                                activeClassName={classes.homeItemLinkActive}
                                onClick={handleClose}
                            >
                                Home
                            </NavLink>
                            {renderNavItems(navigationItems)}
                        </div>
                    </div>
                    <div className={classes.footer}>
                        <div className={classes.switchers}>
                            <StoreSwitcher />
                            <CurrencySwitcher />
                        </div>
                        <AuthBar
                            disabled={hasModal}
                            showMyAccount={showMyAccount}
                            showSignIn={showSignIn}
                        />
                    </div>
                    <div className={modalClassName}>{authModal}</div>
                </aside>
            </FocusScope>
        </Portal>
    );
};

export default Navigation;

Navigation.propTypes = {
    classes: shape({
        body: string,
        form_closed: string,
        form_open: string,
        footer: string,
        header: string,
        root: string,
        root_open: string,
        signIn_closed: string,
        signIn_open: string
    })
};
