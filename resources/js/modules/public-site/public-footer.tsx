import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import type { NavigationItemRecord } from '@/types';

import { PublicNavLink } from './public-navigation';

export function PublicFooter({
    items,
    locale,
}: {
    items: NavigationItemRecord[];
    locale: 'en' | 'ar';
}) {
    const { t } = useTranslator();
    const navigation =
        items.length > 0 ? items : defaultPublicFooterNavigation(t);

    return (
        <footer className="pmc-site-footer">
            <div className="pmc-site-footer-main">
                <section className="pmc-site-footer-brand">
                    <Link href="/" className="pmc-site-brand">
                        <span>PC</span>
                        <div>
                            <strong>{t('public.brand')}</strong>
                            <small>{t('public.brand_subtitle')}</small>
                        </div>
                    </Link>
                    <p>{t('public.footer_description')}</p>
                    <Link href="/login" className="btn btn-primary">
                        {t('public.open_portal')}
                    </Link>
                </section>

                <nav
                    className="pmc-site-footer-links"
                    aria-label={t('public.footer_navigation')}
                >
                    {navigation.map((item) => (
                        <div key={item.id}>
                            <PublicNavLink
                                item={item}
                                locale={locale}
                                onNavigate={() => undefined}
                            />
                            {(item.children ?? []).map((child) => (
                                <PublicNavLink
                                    key={child.id}
                                    item={child}
                                    locale={locale}
                                    onNavigate={() => undefined}
                                />
                            ))}
                        </div>
                    ))}
                </nav>
            </div>

            <div className="pmc-site-footer-bottom">
                <span>
                    {t('public.copyright', undefined, {
                        year: new Date().getFullYear(),
                        name: t('public.brand'),
                    })}
                </span>
                <span>{t('public.bilingual_service')}</span>
            </div>
        </footer>
    );
}

function defaultPublicFooterNavigation(
    t: ReturnType<typeof useTranslator>['t'],
): NavigationItemRecord[] {
    return [
        [t('common.home'), '/'],
        [t('public.features'), '#features'],
        [t('public.workflow'), '#workflow'],
        [t('public.faq'), '#faq'],
    ].map(([title, href], index) => ({
        id: -100 - index,
        title_en: title,
        title_ar: title,
        url: href,
        target: '_self',
        location: 'footer',
        sort_order: index + 1,
    }));
}
