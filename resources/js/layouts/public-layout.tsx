import { Link, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
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

    useEffect(() => {
        document.documentElement.lang = props.app.locale;
        document.documentElement.dir = props.app.direction;
    }, [props.app.direction, props.app.locale]);

    return (
        <div className="pmc-public-shell">
            <header className="pmc-public-header">
                <div className="container">
                    <div className="pmc-public-nav">
                        <Link href="/" className="pmc-public-brand">
                            <span>PMC</span>
                            <strong>{props.app.name}</strong>
                        </Link>

                        <nav
                            className="pmc-public-links"
                            aria-label="Public navigation"
                        >
                            {props.publicNavigation.header.length > 0
                                ? props.publicNavigation.header.map((item) => (
                                      <PublicNavItem
                                          key={item.id}
                                          item={item}
                                          locale={locale}
                                      />
                                  ))
                                : defaultNavigation(locale).map((item) => (
                                      <a key={item.href} href={item.href}>
                                          {item.label}
                                      </a>
                                  ))}
                        </nav>

                        <div className="pmc-public-actions">
                            <LanguageSwitcher />
                            <Link href="/login" className="btn btn-primary">
                                <i className="bi bi-box-arrow-in-right me-2" />
                                {locale === 'ar'
                                    ? 'دخول البوابة'
                                    : 'Open Portal'}
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <main className="pmc-public-main container">{children}</main>
        </div>
    );
}

function PublicNavItem({
    item,
    locale,
}: {
    item: NavigationItemRecord;
    locale: 'en' | 'ar';
}) {
    const href = item.url || '/';
    const label = itemLabel(item, locale);

    if (href.startsWith('#')) {
        return <a href={href}>{label}</a>;
    }

    return <Link href={href}>{label}</Link>;
}

function defaultNavigation(locale: 'en' | 'ar') {
    if (locale === 'ar') {
        return [
            { label: 'المزايا', href: '#features' },
            { label: 'مسار العمل', href: '#workflow' },
            { label: 'الأسئلة', href: '#faq' },
        ];
    }

    return [
        { label: 'Features', href: '#features' },
        { label: 'Workflow', href: '#workflow' },
        { label: 'FAQ', href: '#faq' },
    ];
}
