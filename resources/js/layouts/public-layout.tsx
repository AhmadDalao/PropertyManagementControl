import { Link, usePage } from '@inertiajs/react';
import { useEffect, type ReactNode } from 'react';

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
        <div className="container py-4 py-lg-5">
            <div className="pmc-topbar p-3 p-lg-4 mb-4">
                <div className="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div>
                        <div className="pmc-kicker mb-2">Property management control</div>
                        <div className="h4 fw-bold mb-0">{props.app.name}</div>
                    </div>

                    <div className="d-flex flex-wrap gap-3 align-items-center">
                        <nav className="d-flex gap-3 flex-wrap">
                            {props.publicNavigation.header.map((item) => (
                                <Link key={item.id} href={item.url || '/'} className="fw-semibold text-dark">
                                    {itemLabel(item, locale)}
                                </Link>
                            ))}
                        </nav>
                        <LanguageSwitcher />
                        <Link href="/login" className="btn btn-primary">
                            Login
                        </Link>
                    </div>
                </div>
            </div>

            {children}
        </div>
    );
}
