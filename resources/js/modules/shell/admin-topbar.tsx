import { Link } from '@inertiajs/react';
import type { RefObject } from 'react';

import { GlobalSearch } from '@/components/global-search';
import { LanguageSwitcher } from '@/components/language-switcher';
import { useTranslator } from '@/lib/i18n';
import type { AppUser } from '@/types/auth';

import { AccountMenu } from './account-menu';

type AdminTopbarProps = {
    user: AppUser | null;
    navOpen: boolean;
    drawerViewport: boolean;
    sidebarCollapsed: boolean;
    menuTriggerRef: RefObject<HTMLButtonElement | null>;
    toggleNavigation: () => void;
};

export function AdminTopbar({
    user,
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
