import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';

import { LanguageSwitcher } from '@/components/language-switcher';
import type { NavigationItemRecord, SharedProps } from '@/types';

type PublicLayoutProps = {
    children: ReactNode;
};

function itemLabel(item: NavigationItemRecord, locale: 'en' | 'ar') {
    return locale === 'ar' ? item.title_ar : item.title_en;
}

export function PublicLayout({ children }: PublicLayoutProps) {
    const { props } = usePage<SharedProps>();
    const locale = props.app.locale;
    const [menuOpen, setMenuOpen] = useState(false);

    useEffect(() => {
        document.documentElement.lang = props.app.locale;
        document.documentElement.dir = props.app.direction;
    }, [props.app.direction, props.app.locale]);

    useEffect(() => {
        document.body.classList.toggle('pmc-site-menu-open', menuOpen);

        return () => document.body.classList.remove('pmc-site-menu-open');
    }, [menuOpen]);

    const navItems =
        props.publicNavigation.header.length > 0
            ? props.publicNavigation.header
            : defaultNavigation(locale);

    return (
        <div className="pmc-site-shell">
            <header className="pmc-site-header">
                <div className="pmc-site-nav">
                    <Link href="/" className="pmc-site-brand">
                        <span>PC</span>
                        <div>
                            <strong>Property Control</strong>
                            <small>Portfolio operations OS</small>
                        </div>
                    </Link>

                    <nav
                        className={`pmc-site-links ${menuOpen ? 'is-open' : ''}`}
                        aria-label="Public navigation"
                    >
                        {navItems.map((item) => (
                            <PublicNavItem
                                key={`${item.id}-${item.url}`}
                                item={item}
                                locale={locale}
                                onNavigate={() => setMenuOpen(false)}
                            />
                        ))}
                    </nav>

                    <div className="pmc-site-actions">
                        <LanguageSwitcher />
                        <Link href="/login" className="btn btn-primary">
                            {locale === 'ar' ? 'فتح البوابة' : 'Open Portal'}
                        </Link>
                        <button
                            type="button"
                            className="pmc-site-menu"
                            aria-label="Toggle navigation"
                            aria-expanded={menuOpen}
                            onClick={() => setMenuOpen((value) => !value)}
                        >
                            <i
                                className={`bi ${menuOpen ? 'bi-x-lg' : 'bi-list'}`}
                            />
                        </button>
                    </div>
                </div>
            </header>

            <main className="pmc-site-main">{children}</main>
        </div>
    );
}

function PublicNavItem({
    item,
    locale,
    onNavigate,
}: {
    item: NavigationItemRecord;
    locale: 'en' | 'ar';
    onNavigate: () => void;
}) {
    const href = item.url || '/';
    const label = itemLabel(item, locale);

    if (href.startsWith('#') || href.startsWith('http')) {
        return (
            <a href={href} onClick={onNavigate}>
                {label}
            </a>
        );
    }

    return (
        <Link href={href} onClick={onNavigate}>
            {label}
        </Link>
    );
}

function defaultNavigation(locale: 'en' | 'ar'): NavigationItemRecord[] {
    const items =
        locale === 'ar'
            ? [
                  ['المزايا', '#features'],
                  ['طريقة العمل', '#workflow'],
                  ['الأسئلة', '#faq'],
              ]
            : [
                  ['Features', '#features'],
                  ['Workflow', '#workflow'],
                  ['FAQ', '#faq'],
              ];

    return items.map(([title, href], index) => ({
        id: -index - 1,
        title_en: title,
        title_ar: title,
        url: href,
        target: '_self',
        location: 'header',
        sort_order: index + 1,
    }));
}
