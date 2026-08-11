import { Link } from '@inertiajs/react';
import type { RefObject } from 'react';

import { GlobalSearch } from '@/components/global-search';
import { LanguageSwitcher } from '@/components/language-switcher';
import { useTranslator } from '@/lib/i18n';
import { NotificationMenu } from '@/modules/notifications/notification-menu';
import type { NotificationSummary } from '@/modules/notifications/types';
import type { AppUser } from '@/types/auth';

import { AccountMenu } from './account-menu';
import { QuickActionMenu } from './quick-action-menu';

type AdminTopbarProps = {
    user: AppUser | null;
    notifications: NotificationSummary;
    navOpen: boolean;
    drawerViewport: boolean;
    sidebarCollapsed: boolean;
    menuTriggerRef: RefObject<HTMLButtonElement | null>;
    toggleNavigation: () => void;
};

export function AdminTopbar({
    user,
    notifications,
    navOpen,
    drawerViewport,
    sidebarCollapsed,
    menuTriggerRef,
    toggleNavigation,
}: AdminTopbarProps) {
    const { t } = useTranslator();

    return (
        <header className="pmc-console-topbar">
            <div className="pmc-topbar-left">
                <button
                    ref={menuTriggerRef}
                    type="button"
                    className="pmc-menu-trigger"
                    aria-label={t('shell.toggle_navigation')}
                    aria-expanded={drawerViewport ? navOpen : !sidebarCollapsed}
                    onClick={toggleNavigation}
                >
                    <i className="bi bi-layout-sidebar-inset" />
                    <span>{t('shell.navigation')}</span>
                </button>
            </div>

            {user ? <GlobalSearch /> : null}

            <div className="pmc-topbar-actions">
                {user ? <QuickActionMenu user={user} /> : null}
                {user ? (
                    <NotificationMenu notifications={notifications} />
                ) : null}
                <LanguageSwitcher />
                {user ? (
                    <AccountMenu user={user} />
                ) : (
                    <Link href="/login" className="btn btn-primary btn-sm">
                        {t('nav.login')}
                    </Link>
                )}
            </div>
        </header>
    );
}
