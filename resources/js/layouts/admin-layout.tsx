import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';

import { FlashBanner } from '@/components/flash-banner';
import { GlobalSearch } from '@/components/global-search';
import { LanguageSwitcher } from '@/components/language-switcher';
import { useTranslator } from '@/lib/i18n';
import { MODULE_NAV_GROUPS } from '@/modules/registry';
import type { ModuleNavItem } from '@/modules/registry';
import type { SharedProps } from '@/types';

type AdminLayoutProps = {
    children: ReactNode;
};

export function AdminLayout({ children }: AdminLayoutProps) {
    const { props, url } = usePage<SharedProps>();
    const user = props.auth.user;
    const roles = user?.roles ?? [];
    const [navOpen, setNavOpen] = useState(false);
    const [drawerViewport, setDrawerViewport] = useState(
        () =>
            typeof window !== 'undefined' &&
            window.matchMedia('(max-width: 1199.98px)').matches,
    );
    const [sidebarCollapsed, setSidebarCollapsed] = useState(
        () =>
            typeof window !== 'undefined' &&
            window.localStorage.getItem('property-sidebar-collapsed') === '1',
    );
    const sidebarRef = useRef<HTMLElement>(null);
    const menuTriggerRef = useRef<HTMLButtonElement>(null);
    const { t } = useTranslator();

    useEffect(() => {
        document.documentElement.lang = props.app.locale;
        document.documentElement.dir = props.app.direction;
    }, [props.app.direction, props.app.locale]);

    useEffect(() => {
        const media = window.matchMedia('(max-width: 1199.98px)');
        const updateViewport = () => setDrawerViewport(media.matches);

        media.addEventListener('change', updateViewport);

        return () => media.removeEventListener('change', updateViewport);
    }, []);

    useEffect(() => {
        if (!navOpen) {
            document.body.classList.remove('pmc-drawer-open');

            return;
        }

        document.body.classList.add('pmc-drawer-open');
        const firstFocusable = sidebarRef.current?.querySelector<HTMLElement>(
            'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])',
        );
        firstFocusable?.focus();

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setNavOpen(false);
                menuTriggerRef.current?.focus();

                return;
            }

            if (event.key !== 'Tab' || !sidebarRef.current) {
                return;
            }

            const focusable = Array.from(
                sidebarRef.current.querySelectorAll<HTMLElement>(
                    'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])',
                ),
            ).filter((element) => !element.hasAttribute('disabled'));

            if (focusable.length === 0) {
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            document.body.classList.remove('pmc-drawer-open');
        };
    }, [navOpen]);

    const toggleNavigation = () => {
        if (drawerViewport) {
            setNavOpen((open) => !open);

            return;
        }

        setSidebarCollapsed((collapsed) => {
            const next = !collapsed;
            window.localStorage.setItem(
                'property-sidebar-collapsed',
                next ? '1' : '0',
            );

            return next;
        });
    };

    const closeDrawer = () => {
        setNavOpen(false);

        if (drawerViewport) {
            menuTriggerRef.current?.focus();
        }
    };

    const hasRoleAccess = (item: ModuleNavItem) =>
        !item.roles || item.roles.some((role) => roles.includes(role));
    const hasModuleAccess = (module?: string) => {
        if (!module || roles.includes('superadmin')) {
            return true;
        }

        if (!user?.portfolio) {
            return false;
        }

        return user.portfolio.module_settings[module] !== false;
    };
    const canUse = (item: ModuleNavItem) =>
        hasRoleAccess(item) && hasModuleAccess(item.module);

    const visibleGroups = MODULE_NAV_GROUPS.map((group) => ({
        ...group,
        items: group.items.filter((item) => canUse(item)),
    })).filter((group) => group.items.length > 0);

    return (
        <div
            className={`pmc-console-shell ${sidebarCollapsed ? 'is-collapsed' : ''} ${navOpen ? 'is-open' : ''}`}
        >
            <button
                type="button"
                className={`pmc-sidebar-backdrop ${navOpen ? 'is-open' : ''}`}
                aria-label={t('shell.close_navigation')}
                onClick={closeDrawer}
            />

            <aside
                ref={sidebarRef}
                className="pmc-console-sidebar"
                aria-label={t('shell.navigation', 'Main navigation')}
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
                        aria-label={t(
                            'shell.close_navigation',
                            'Close navigation',
                        )}
                        onClick={closeDrawer}
                    >
                        <i className="bi bi-x-lg" />
                    </button>
                </div>

                <nav
                    className="pmc-console-nav"
                    aria-label={t('shell.navigation', 'Main navigation')}
                >
                    {visibleGroups.map((group) => (
                        <section key={group.labelKey}>
                            <p>{t(group.labelKey)}</p>
                            {group.items.map((item) => (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    onClick={closeDrawer}
                                    className={`pmc-nav-link ${isActivePath(url, item.href) ? 'active' : ''}`}
                                    title={t(item.labelKey)}
                                >
                                    <i className={`bi ${item.icon}`} />
                                    <span>{t(item.labelKey)}</span>
                                </Link>
                            ))}
                        </section>
                    ))}
                </nav>

                <button
                    type="button"
                    className="pmc-sidebar-collapse"
                    onClick={toggleNavigation}
                    aria-label={
                        sidebarCollapsed
                            ? t('shell.expand_navigation', 'Expand navigation')
                            : t(
                                  'shell.collapse_navigation',
                                  'Collapse navigation',
                              )
                    }
                >
                    <i
                        className={`bi ${
                            sidebarCollapsed
                                ? 'bi-chevron-double-right'
                                : 'bi-chevron-double-left'
                        }`}
                    />
                    <span>
                        {t('shell.collapse_navigation', 'Collapse navigation')}
                    </span>
                </button>
            </aside>

            <main className="pmc-console-main">
                <header className="pmc-console-topbar">
                    <div className="pmc-topbar-left">
                        <button
                            ref={menuTriggerRef}
                            type="button"
                            className="pmc-menu-trigger"
                            aria-label={t(
                                'shell.toggle_navigation',
                                'Toggle navigation',
                            )}
                            aria-expanded={
                                drawerViewport ? navOpen : !sidebarCollapsed
                            }
                            onClick={toggleNavigation}
                        >
                            <i className="bi bi-layout-sidebar-inset" />
                            <span>{t('shell.navigation', 'Navigation')}</span>
                        </button>
                    </div>

                    {user ? <GlobalSearch /> : null}

                    <div className="pmc-topbar-actions">
                        <LanguageSwitcher />
                        {user ? (
                            <details className="pmc-account-menu">
                                <summary>
                                    <span>
                                        {user.name.slice(0, 1).toUpperCase()}
                                    </span>
                                    <div>
                                        <strong>{user.name}</strong>
                                        <small>{user.email}</small>
                                    </div>
                                </summary>
                                <div className="pmc-account-panel">
                                    <Link href="/profile">
                                        {t('nav.profile', 'Profile')}
                                    </Link>
                                    <Link href="/dashboard">
                                        {t('nav.dashboard', 'Dashboard')}
                                    </Link>
                                    <Link href="/documentation">
                                        {t(
                                            'nav.documentation',
                                            'Documentation',
                                        )}
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => router.post('/logout')}
                                    >
                                        {t('nav.logout', 'Logout')}
                                    </button>
                                </div>
                            </details>
                        ) : (
                            <Link
                                href="/login"
                                className="btn btn-primary btn-sm"
                            >
                                {t('nav.login', 'Login')}
                            </Link>
                        )}
                    </div>
                </header>

                <section className="pmc-console-content">
                    <FlashBanner />
                    {user?.force_password_reset && url !== '/profile' ? (
                        <div className="alert alert-warning d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center mb-4 border-0">
                            <div>
                                <strong>
                                    {t(
                                        'shell.temporary_password',
                                        'Temporary password active.',
                                    )}
                                </strong>{' '}
                                {t(
                                    'shell.update_password_notice',
                                    'Update your password before continuing account work.',
                                )}
                            </div>
                            <Link
                                href="/profile"
                                className="btn btn-warning btn-sm"
                            >
                                {t('shell.open_profile', 'Open profile')}
                            </Link>
                        </div>
                    ) : null}
                    {children}
                </section>
            </main>
        </div>
    );
}

function isActivePath(currentUrl: string, href: string): boolean {
    const path = currentUrl.split('?')[0];

    return path === href || path.startsWith(`${href}/`);
}
