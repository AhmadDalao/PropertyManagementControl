import { router } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import type { PaginatedData } from '@/types';

import { cleanPaginationLabel, interpolate } from './table-utils';

export function TablePagination<T>({ data }: { data: PaginatedData<T> }) {
    const { t } = useTranslator();
    const previousLink = data.links.at(0);
    const nextLink = data.links.at(-1);

    const visit = (url: string | null | undefined) => {
        if (!url) {
            return;
        }

        router.visit(url, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <div className="pmc-table-footer">
            <p className="pmc-table-results" aria-live="polite">
                {interpolate(
                    t(
                        'table.showing',
                        'Showing :from to :to of :total entries',
                    ),
                    {
                        from: data.from ?? 0,
                        to: data.to ?? 0,
                        total: data.total,
                    },
                )}
            </p>
            <div
                className="pmc-table-pagination pmc-table-pagination-desktop"
                aria-label={t('pagination.navigation', 'Pagination')}
            >
                {data.links.map((link, index) => (
                    <button
                        key={`${link.label}-${index}`}
                        type="button"
                        className={`pmc-page-button ${link.active ? 'active' : ''}`}
                        disabled={!link.url}
                        aria-current={link.active ? 'page' : undefined}
                        onClick={() => visit(link.url)}
                    >
                        {cleanPaginationLabel(link.label)}
                    </button>
                ))}
            </div>
            <div
                className="pmc-table-pagination-mobile"
                aria-label={t('pagination.navigation', 'Pagination')}
            >
                <button
                    type="button"
                    disabled={!previousLink?.url}
                    onClick={() => visit(previousLink?.url)}
                >
                    <i className="bi bi-chevron-left" />
                    <span>{t('pagination.previous', 'Previous')}</span>
                </button>
                <strong>
                    {t('pagination.page_of', 'Page :page of :pages', {
                        page: data.current_page,
                        pages: data.last_page,
                    })}
                </strong>
                <button
                    type="button"
                    disabled={!nextLink?.url}
                    onClick={() => visit(nextLink?.url)}
                >
                    <span>{t('pagination.next', 'Next')}</span>
                    <i className="bi bi-chevron-right" />
                </button>
            </div>
        </div>
    );
}
