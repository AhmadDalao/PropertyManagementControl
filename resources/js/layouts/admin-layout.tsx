import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, type ReactNode } from 'react';

import { FlashBanner } from '@/components/flash-banner';
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

const NAV_ITEMS: NavItem[] = [
    { label: 'Dashboard', href: '/dashboard', icon: 'bi-grid' },
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
        icon: 'bi-building',
        roles: ['superadmin', 'owner', 'property_manager'],
    },
    {
        label: 'Tenants',
        href: '/tenants',
        icon: 'bi-person-badge',
        roles: ['superadmin', 'owner', 'property_manager'],
    },
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
];

export function AdminLayout({ children }: AdminLayoutProps) {
    const { props, url } = usePage<SharedProps>();
    const user = props.auth.user;
    const roles = user?.roles ?? [];

    useEffect(() => {
        document.documentElement.lang = props.app.locale;
        document.documentElement.dir = props.app.direction;
    }, [props.app.direction, props.app.locale]);

    const visibleItems = NAV_ITEMS.filter(
        (item) =>
            !item.roles ||
            item.roles.some((role) => roles.includes(role)),
    );

    return (
        <div className="pmc-shell d-flex flex-column flex-lg-row">
            <aside className="pmc-sidebar p-4">
                <div className="mb-5">
                    <div className="pmc-kicker text-white-50 mb-2">Property control</div>
                    <div className="h3 fw-bold mb-1">{props.app.name}</div>
                    <div className="small text-white-50">
                        {user?.name ?? 'Guest'}
                    </div>
                </div>

                <nav className="nav flex-column">
                    {visibleItems.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`nav-link ${url.startsWith(item.href) ? 'active' : ''}`}
                        >
                            <i className={`bi ${item.icon}`} />
                            <span>{item.label}</span>
                        </Link>
                    ))}
                </nav>
            </aside>

            <main className="pmc-main p-3 p-lg-4">
                <div className="pmc-topbar p-3 p-lg-4 mb-4">
                    <div className="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                        <div>
                            <div className="pmc-kicker mb-2">
                                {roles.join(' / ') || 'Visitor'}
                            </div>
                            <div className="h5 mb-0 fw-bold">
                                {user ? `Signed in as ${user.name}` : 'Public view'}
                            </div>
                        </div>
                        <div className="d-flex flex-wrap gap-2 align-items-center">
                            <LanguageSwitcher />
                            {user ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={() => router.post('/logout')}
                                >
                                    <i className="bi bi-box-arrow-right me-2" />
                                    Logout
                                </button>
                            ) : (
                                <Link href="/login" className="btn btn-primary btn-sm">
                                    Login
                                </Link>
                            )}
                        </div>
                    </div>
                </div>

                <FlashBanner />
                {children}
            </main>
        </div>
    );
}
