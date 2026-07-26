import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type {
    PortfolioControlFilters as FilterState,
    PortfolioControlProps,
} from './types';

export function PortfolioControlFilters({
    filters,
    counts,
    portfolioOptions,
}: Pick<PortfolioControlProps, 'filters' | 'counts' | 'portfolioOptions'>) {
    const { locale, t } = useTranslator();
    const [draft, setDraft] = useState({
        search: filters.search,
        portfolio_id: filters.portfolio_id
            ? String(filters.portfolio_id)
            : 'all',
        sort: filters.sort,
        per_page: String(filters.per_page),
    });
    const visit = (
        overrides: Partial<FilterState> &
            Record<string, string | number | null>,
    ) => {
        const next = {
            search: draft.search || undefined,
            attention: filters.attention,
            portfolio_id:
                draft.portfolio_id === 'all' ? undefined : draft.portfolio_id,
            sort: draft.sort,
            per_page: draft.per_page,
            ...overrides,
            page: 1,
        };

        router.get('/portfolio-control', next, {
            preserveScroll: true,
            preserveState: false,
            replace: true,
        });
    };
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        visit({});
    };
    const reset = () =>
        router.get(
            '/portfolio-control',
            {},
            {
                preserveScroll: true,
                preserveState: false,
                replace: true,
            },
        );

    return (
        <section className="pmc-portfolio-control-filters">
            <div
                className="pmc-portfolio-control-chips"
                aria-label={t('portfolio_control.attention_filter')}
            >
                {counts.map((count) => (
                    <button
                        key={count.key}
                        type="button"
                        className={
                            filters.attention === count.key ? 'is-active' : ''
                        }
                        aria-pressed={filters.attention === count.key}
                        onClick={() => visit({ attention: count.key })}
                    >
                        <span>
                            {t(`portfolio_control.attention_${count.key}`)}
                        </span>
                        <strong>{localizedNumber(count.count, locale)}</strong>
                    </button>
                ))}
            </div>

            <form onSubmit={submit}>
                <label>
                    <span>{t('actions.search')}</span>
                    <input
                        type="search"
                        value={draft.search}
                        placeholder={t('portfolio_control.search_placeholder')}
                        onChange={(event) =>
                            setDraft({
                                ...draft,
                                search: event.currentTarget.value,
                            })
                        }
                    />
                </label>
                <label>
                    <span>{t('portfolio_control.portfolio')}</span>
                    <select
                        value={draft.portfolio_id}
                        onChange={(event) =>
                            setDraft({
                                ...draft,
                                portfolio_id: event.currentTarget.value,
                            })
                        }
                    >
                        <option value="all">
                            {t('portfolio_control.all_portfolios')}
                        </option>
                        {portfolioOptions.map((portfolio) => (
                            <option key={portfolio.id} value={portfolio.id}>
                                {locale === 'ar'
                                    ? portfolio.name_ar || portfolio.name_en
                                    : portfolio.name_en ||
                                      portfolio.name_ar}{' '}
                                · {portfolio.code}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    <span>{t('portfolio_control.sort')}</span>
                    <select
                        value={draft.sort}
                        onChange={(event) =>
                            setDraft({
                                ...draft,
                                sort: event.currentTarget
                                    .value as FilterState['sort'],
                            })
                        }
                    >
                        {[
                            'attention',
                            'arrears',
                            'occupancy',
                            'collection',
                            'net',
                            'name',
                        ].map((sort) => (
                            <option key={sort} value={sort}>
                                {t(`portfolio_control.sort_${sort}`)}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    <span>{t('table.show')}</span>
                    <select
                        value={draft.per_page}
                        onChange={(event) =>
                            setDraft({
                                ...draft,
                                per_page: event.currentTarget.value,
                            })
                        }
                    >
                        {[12, 24, 48].map((size) => (
                            <option key={size} value={size}>
                                {localizedNumber(size, locale)}
                            </option>
                        ))}
                    </select>
                </label>
                <div className="pmc-portfolio-control-filter-actions">
                    <button type="submit">
                        <i className="bi bi-funnel" aria-hidden="true" />
                        {t('actions.filter')}
                    </button>
                    <button type="button" onClick={reset}>
                        <i
                            className="bi bi-arrow-counterclockwise"
                            aria-hidden="true"
                        />
                        {t('actions.reset')}
                    </button>
                </div>
            </form>
        </section>
    );
}
