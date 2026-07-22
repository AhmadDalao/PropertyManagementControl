import { Link, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import type { ReactNode } from 'react';

import { LanguageSwitcher } from '@/components/language-switcher';
import { useTranslator } from '@/lib/i18n';
import type { SharedProps } from '@/types';

import { defaultPublicNavigation, PublicNavigation } from './public-navigation';
import { usePublicMenu } from './use-public-menu';

export function PublicLayout({ children }: { children: ReactNode }) {
    const { props } = usePage<SharedProps>();
    const { t } = useTranslator();
    const { closeMenu, menuOpen, navigationRef, setMenuOpen, triggerRef } =
        usePublicMenu();
    const navItems =
        props.publicNavigation.header.length > 0
            ? props.publicNavigation.header
            : defaultPublicNavigation(t);

    useEffect(() => {
        document.documentElement.lang = props.app.locale;
        document.documentElement.dir = props.app.direction;
    }, [props.app.direction, props.app.locale]);

    return (
        <div className="pmc-site-shell">
            <header className="pmc-site-header">
                <div className="pmc-site-nav">
                    <Link href="/" className="pmc-site-brand">
                        <span>PC</span>
                        <div>
                            <strong>{t('public.brand')}</strong>
                            <small>{t('public.brand_subtitle')}</small>
                        </div>
                    </Link>

                    <PublicNavigation
                        items={navItems}
                        locale={props.app.locale}
                        menuOpen={menuOpen}
                        navigationRef={navigationRef}
                        onNavigate={closeMenu}
                    />

                    <div className="pmc-site-actions">
                        <LanguageSwitcher />
                        <Link href="/login" className="btn btn-primary">
                            {t('public.open_portal')}
                        </Link>
                        <button
                            ref={triggerRef}
                            type="button"
                            className="pmc-site-menu"
                            aria-label={t('public.toggle_navigation')}
                            aria-expanded={menuOpen}
                            aria-controls="public-navigation"
                            onClick={() => setMenuOpen(!menuOpen)}
                        >
                            <i
                                className={`bi ${menuOpen ? 'bi-x-lg' : 'bi-list'}`}
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                </div>
            </header>

            {menuOpen ? (
                <button
                    type="button"
                    className="pmc-site-menu-backdrop"
                    aria-label={t('common.close')}
                    onClick={closeMenu}
                />
            ) : null}

            <main className="pmc-site-main">{children}</main>
        </div>
    );
}
