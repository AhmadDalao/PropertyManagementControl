import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';

import { FlashBanner } from '@/components/flash-banner';
import { GlobalSearch } from '@/components/global-search';
import { LanguageSwitcher } from '@/components/language-switcher';
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
    const [sidebarCollapsed, setSidebarCollapsed] = useState(
        () =>
            typeof window !== 'undefined' &&
            window.localStorage.getItem('property-sidebar-collapsed') === '1',
    );

    useEffect(() => {
        document.documentElement.lang = props.app.locale;
        document.documentElement.dir = props.app.direction;
    }, [props.app.direction, props.app.locale]);

    const isDrawerViewport = () =>
        window.matchMedia('(max-width: 1199.98px)').matches;

    const toggleNavigation = () => {
        if (isDrawerViewport()) {
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

    const closeDrawer = () => setNavOpen(false);

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
                aria-label="Close navigation"
                onClick={closeDrawer}
            />

            <aside className="pmc-console-sidebar">
                <div className="pmc-console-brand">
                    <Link href="/dashboard" className="pmc-brand-mark">
                        PC
                    </Link>
                    <div>
                        <span>Property Control</span>
                        <strong>{props.app.name}</strong>
                    </div>
                    <button
                        type="button"
                        className="pmc-mobile-close"
                        aria-label="Close navigation"
                        onClick={closeDrawer}
                    >
                        <i className="bi bi-x-lg" />
                    </button>
                </div>

                <nav className="pmc-console-nav" aria-label="Admin navigation">
                    {visibleGroups.map((group) => (
                        <section key={group.label}>
                            <p>{group.label}</p>
                            {group.items.map((item) => (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    onClick={closeDrawer}
                                    className={`pmc-nav-link ${url.startsWith(item.href) ? 'active' : ''}`}
                                    title={item.label}
                                >
                                    <i className={`bi ${item.icon}`} />
                                    <span>{item.label}</span>
                                </Link>
                            ))}
                        </section>
                    ))}
                </nav>

                <div className="pmc-sidebar-help">
                    <strong>Need the workflow?</strong>
                    <span>Open Documentation before touching live data.</span>
                    <Link href="/documentation">Read guides</Link>
                </div>
            </aside>

            <main className="pmc-console-main">
                <header className="pmc-console-topbar">
                    <div className="pmc-topbar-left">
                        <button
                            type="button"
                            className="pmc-menu-trigger"
                            aria-label="Toggle navigation"
                            aria-expanded={navOpen || !sidebarCollapsed}
                            onClick={toggleNavigation}
                        >
                            <i className="bi bi-layout-sidebar-inset" />
                            <span>Navigation</span>
                        </button>
                    </div>

                    {user ? <GlobalSearch /> : null}

                    <div className="pmc-topbar-actions">
                        <button
                            type="button"
                            className="pmc-notification-trigger"
                            aria-label="Notifications"
                        >
                            <i className="bi bi-bell" />
                        </button>
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
                                    <Link href="/profile">Profile</Link>
                                    <Link href="/dashboard">Dashboard</Link>
                                    <Link href="/documentation">
                                        Documentation
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => router.post('/logout')}
                                    >
                                        Logout
                                    </button>
                                </div>
                            </details>
                        ) : (
                            <Link
                                href="/login"
                                className="btn btn-primary btn-sm"
                            >
                                Login
                            </Link>
                        )}
                    </div>
                </header>

                <section className="pmc-console-content">
                    <FlashBanner />
                    {user?.force_password_reset && url !== '/profile' ? (
                        <div className="alert alert-warning d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center mb-4 border-0">
                            <div>
                                <strong>Temporary password active.</strong>{' '}
                                Update your password before continuing serious
                                account work.
                            </div>
                            <Link
                                href="/profile"
                                className="btn btn-warning btn-sm"
                            >
                                Open profile
                            </Link>
                        </div>
                    ) : null}
                    {children}
                </section>
            </main>
        </div>
    );
}
