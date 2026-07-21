import { Link } from '@inertiajs/react';
import type { RefObject } from 'react';

import { useTranslator } from '@/lib/i18n';
import type { AppUser } from '@/types/auth';

import { isActivePath, visibleNavigationGroups } from './navigation-access';

type AdminSidebarProps = {
    currentUrl: string;
    user: AppUser | null;
    direction: 'ltr' | 'rtl';
    navOpen: boolean;
    drawerViewport: boolean;
    sidebarCollapsed: boolean;
    sidebarRef: RefObject<HTMLElement | null>;
    closeDrawer: () => void;
    toggleNavigation: () => void;
};

export function AdminSidebar({
    currentUrl,
    user,
    direction,
    navOpen,
    drawerViewport,
    sidebarCollapsed,
    sidebarRef,
    closeDrawer,
    toggleNavigation,
}: AdminSidebarProps) {
    const { t } = useTranslator();
    const groups = visibleNavigationGroups(user);
    const drawerHidden = drawerViewport && !navOpen;
    const collapseIcon = sidebarCollapsed
        ? direction === 'rtl'
            ? 'bi-chevron-double-left'
            : 'bi-chevron-double-right'
        : direction === 'rtl'
          ? 'bi-chevron-double-right'
          : 'bi-chevron-double-left';

    return (
        <>
            <button
                type="button"
                className={`pmc-sidebar-backdrop ${navOpen ? 'is-open' : ''}`}
                aria-label={t('shell.close_navigation')}
                aria-hidden={!navOpen}
                tabIndex={navOpen ? 0 : -1}
                onClick={closeDrawer}
            />

            <aside
                ref={sidebarRef}
                className="pmc-console-sidebar"
                aria-label={t('shell.navigation', 'Main navigation')}
                aria-hidden={drawerHidden}
                inert={drawerHidden}
            >
                <div className="pmc-console-brand">
                    <Link href="/dashboard" className="pmc-brand-mark">
                        PC
                    </Link>
                    <div>
                        <span>{t('shell.brand', 'Property Control')}</span>
                        <strong>
                            {t('shell.brand_subtitle', 'Operations Suite')}
                        </strong>
                    </div>
                    <button
                        type="button"
                        className="pmc-mobile-close"
                        aria-label={t('shell.close_navigation')}
                        onClick={closeDrawer}
                    >
                        <i className="bi bi-x-lg" />
                    </button>
                </div>

                <nav
                    className="pmc-console-nav"
                    aria-label={t('shell.navigation', 'Main navigation')}
                >
                    {groups.map((group) => (
                        <section key={group.labelKey}>
                            <p>{t(group.labelKey)}</p>
                            {group.items.map((item) => {
                                const active = isActivePath(
                                    currentUrl,
                                    item.href,
                                );

                                return (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        onClick={closeDrawer}
                                        className={`pmc-nav-link ${active ? 'active' : ''}`}
                                        title={t(item.labelKey)}
                                        aria-current={
                                            active ? 'page' : undefined
                                        }
                                    >
                                        <i className={`bi ${item.icon}`} />
                                        <span>{t(item.labelKey)}</span>
                                    </Link>
                                );
                            })}
                        </section>
                    ))}
                </nav>

                <button
                    type="button"
                    className="pmc-sidebar-collapse"
                    onClick={toggleNavigation}
                    aria-label={
                        sidebarCollapsed
                            ? t('shell.expand_navigation')
                            : t('shell.collapse_navigation')
                    }
                    aria-expanded={!sidebarCollapsed}
                >
                    <i className={`bi ${collapseIcon}`} />
                    <span>{t('shell.collapse_navigation')}</span>
                </button>
            </aside>
        </>
    );
}
