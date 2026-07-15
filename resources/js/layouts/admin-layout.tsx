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
    const [menuOpen, setMenuOpen] = useState(false);

    useEffect(() => {
        document.documentElement.lang = props.app.locale;
        document.documentElement.dir = props.app.direction;
    }, [props.app.direction, props.app.locale]);

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
    const canCreateAsset =
        roles.some((role) =>
            ['superadmin', 'owner', 'property_manager'].includes(role),
        ) && hasModuleAccess('assets');
    const canOpenMaintenance = hasModuleAccess('maintenance');

    return (
        <div className="pmc-console-shell">
            <button
                type="button"
                className={`pmc-sidebar-backdrop ${menuOpen ? 'is-open' : ''}`}
                aria-label="Close navigation"
                onClick={() => setMenuOpen(false)}
            />

            <aside
                className={`pmc-console-sidebar ${menuOpen ? 'is-open' : ''}`}
            >
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
                        onClick={() => setMenuOpen(false)}
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
                                    onClick={() => setMenuOpen(false)}
                                    className={`pmc-nav-link ${url.startsWith(item.href) ? 'active' : ''}`}
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
                            aria-label="Open navigation"
                            onClick={() => setMenuOpen(true)}
                        >
                            <i className="bi bi-list" />
                            <span>Menu</span>
                        </button>
                        <div>
                            <span className="pmc-eyebrow">
                                {roles.join(' / ') || 'Visitor'}
                            </span>
                            <h1>Control center</h1>
                        </div>
                    </div>

                    {user ? <GlobalSearch /> : null}

                    <div className="pmc-topbar-actions">
                        {canCreateAsset ? (
                            <Link
                                href="/assets/create"
                                className="btn btn-outline-secondary btn-sm pmc-quick-action"
                            >
                                <i className="bi bi-plus-circle" />
                                Create asset
                            </Link>
                        ) : null}
                        {canOpenMaintenance ? (
                            <Link
                                href="/maintenance-requests/create"
                                className="btn btn-outline-secondary btn-sm pmc-quick-action"
                            >
                                <i className="bi bi-tools" />
                                Create request
                            </Link>
                        ) : null}
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
