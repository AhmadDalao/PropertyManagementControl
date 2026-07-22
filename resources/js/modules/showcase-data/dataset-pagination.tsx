import { router } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { ShowcaseDataPageProps } from './types';

export function DatasetPagination({
    datasets,
}: {
    datasets: ShowcaseDataPageProps['datasets'];
}) {
    const { t } = useTranslator();
    const previous = datasets.links.at(0);
    const next = datasets.links.at(-1);

    if (datasets.last_page <= 1) {
        return null;
    }

    const visit = (url: string | null | undefined) => {
        if (url) {
            router.visit(url, { preserveScroll: true, preserveState: true });
        }
    };

    return (
        <nav
            className="pmc-showcase-pagination"
            aria-label={t('pagination.navigation')}
        >
            <button
                type="button"
                disabled={!previous?.url}
                onClick={() => visit(previous?.url)}
            >
                <i className="bi bi-chevron-left" aria-hidden="true" />
                {t('pagination.previous')}
            </button>
            <span>
                {t('pagination.page_of', undefined, {
                    page: datasets.current_page,
                    pages: datasets.last_page,
                })}
            </span>
            <button
                type="button"
                disabled={!next?.url}
                onClick={() => visit(next?.url)}
            >
                {t('pagination.next')}
                <i className="bi bi-chevron-right" aria-hidden="true" />
            </button>
        </nav>
    );
}
