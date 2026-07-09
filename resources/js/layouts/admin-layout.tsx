import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';

import { FlashBanner } from '@/components/flash-banner';
import { GlobalSearch } from '@/components/global-search';
import { LanguageSwitcher } from '@/components/language-switcher';
import type { SharedProps } from '@/types';

type AdminLayoutProps = {
    children: ReactNode;
};

type NavItem = {
    label: string;
    href: string;
    icon: string;
    roles?: string[];
};

type NavGroup = {
    label: string;
    items: NavItem[];
};

const NAV_GROUPS: NavGroup[] = [
    {
        label: 'Command',
        items: [
            { label: 'Dashboard', href: '/dashboard', icon: 'bi-grid-1x2' },
            {
                label: 'Documentation',
                href: '/documentation',
                icon: 'bi-journal-richtext',
            },
        ],
    },
    {
        label: 'Portfolio',
        items: [
            {
                label: 'Portfolios',
                href: '/portfolios',
                icon: 'bi-buildings',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
            {
                label: 'Users',
                href: '/users',
                icon: 'bi-people',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
            {
                label: 'Assets',
                href: '/assets',
                icon: 'bi-diagram-3',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
            {
                label: 'Tenants',
                href: '/tenants',
                icon: 'bi-person-badge',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
        ],
    },
    {
        label: 'Operations',
        items: [
            {
                label: 'Leases',
                href: '/leases',
                icon: 'bi-file-earmark-text',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
            {
                label: 'Payments',
                href: '/payments',
                icon: 'bi-cash-stack',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
            {
                label: 'Maintenance',
                href: '/maintenance-requests',
                icon: 'bi-tools',
            },
            {
                label: 'Expenses',
                href: '/expenses',
                icon: 'bi-receipt',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
            {
                label: 'Reports',
                href: '/reports',
                icon: 'bi-graph-up-arrow',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
            {
                label: 'Documents',
                href: '/documents',
                icon: 'bi-folder2-open',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
        ],
    },
    {
        label: 'Website',
        items: [
            {
                label: 'Website Control',
                href: '/cms',
                icon: 'bi-layout-wtf',
                roles: ['superadmin'],
            },
            {
                label: 'Media',
                href: '/media-files',
                icon: 'bi-images',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
        ],
    },
];

export function AdminLayout({ children }: AdminLayoutProps) {
    const { props, url } = usePage<SharedProps>();
    const user = props.auth.user;
    const roles = user?.roles ?? [];
    const [menuOpen, setMenuOpen] = useState(false);

    useEffect(() => {
        document.documentElement.lang = props.app.locale;
        document.documentElement.dir = props.app.direction;
    }, [props.app.direction, props.app.locale]);

    const visibleGroups = NAV_GROUPS.map((group) => ({
        ...group,
        items: group.items.filter(
            (item) =>
                !item.roles || item.roles.some((role) => roles.includes(role)),
        ),
    })).filter((group) => group.items.length > 0);

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
                        <Link
                            href="/assets"
                            className="btn btn-outline-secondary btn-sm pmc-quick-action"
                        >
                            <i className="bi bi-plus-circle" />
                            Asset
                        </Link>
                        <Link
                            href="/maintenance-requests"
                            className="btn btn-outline-secondary btn-sm pmc-quick-action"
                        >
                            <i className="bi bi-tools" />
                            Request
                        </Link>
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
                    {children}
                </section>
            </main>
        </div>
    );
}
