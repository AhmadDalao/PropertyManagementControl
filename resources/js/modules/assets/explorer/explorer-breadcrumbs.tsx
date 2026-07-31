import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { PropertyExplorerPayload } from './types';

export function ExplorerBreadcrumbs({
    breadcrumbs,
}: Pick<PropertyExplorerPayload, 'breadcrumbs'>) {
    const { locale, t } = useTranslator();

    return (
        <nav
            className="pmc-explorer-breadcrumbs"
            aria-label={t('assets.explorer.breadcrumbs')}
        >
            {breadcrumbs.map((item, index) => {
                const current = index === breadcrumbs.length - 1;

                return (
                    <span key={item.id}>
                        {index > 0 ? (
                            <i
                                className="bi bi-chevron-right"
                                aria-hidden="true"
                            />
                        ) : null}
                        {current ? (
                            <strong>{localized(item, locale)}</strong>
                        ) : (
                            <Link href={item.href}>
                                {localized(item, locale)}
                            </Link>
                        )}
                    </span>
                );
            })}
        </nav>
    );
}

function localized(
    record: { title_en: string; title_ar?: string | null },
    locale: string,
) {
    return locale === 'ar'
        ? record.title_ar || record.title_en
        : record.title_en || record.title_ar || '';
}
