import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type {
    CompanyControlFilters as FilterState,
    CompanyControlProps,
} from './types';

export function CompanyControlFilters({
    filters,
    counts,
}: Pick<CompanyControlProps, 'filters' | 'counts'>) {
    const { locale, t } = useTranslator();
    const [draft, setDraft] = useState({
        search: filters.search,
        data_source: filters.data_source,
        status: filters.status,
        sort: filters.sort,
        direction: filters.direction,
        per_page: String(filters.per_page),
    });
    const visit = (
        overrides: Partial<FilterState> &
            Record<string, string | number | null>,
    ) =>
        router.get(
            '/company-control',
            {
                ...draft,
                attention: filters.attention,
                ...overrides,
                page: 1,
            },
            {
                preserveScroll: true,
                preserveState: false,
                replace: true,
            },
        );
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        visit({});
    };

    return (
        <section className="pmc-company-control-filters">
            <div
                className="pmc-company-control-chips"
                aria-label={t('company_control.health_filter')}
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
                            {t(`company_control.attention_${count.key}`)}
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
                        placeholder={t('company_control.search_placeholder')}
                        onChange={(event) =>
                            setDraft({
                                ...draft,
                                search: event.currentTarget.value,
                            })
                        }
                    />
                </label>
                <label>
                    <span>{t('company_control.data_source')}</span>
                    <select
                        value={draft.data_source}
                        onChange={(event) =>
                            setDraft({
                                ...draft,
                                data_source: event.currentTarget
                                    .value as FilterState['data_source'],
                            })
                        }
                    >
                        {['live', 'showcase', 'all'].map((source) => (
                            <option key={source} value={source}>
                                {t(`company_control.source_${source}`)}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    <span>{t('company_control.status')}</span>
                    <select
                        value={draft.status}
                        onChange={(event) =>
                            setDraft({
                                ...draft,
                                status: event.currentTarget
                                    .value as FilterState['status'],
                            })
                        }
                    >
                        {['active', 'inactive', 'archived', 'all'].map(
                            (status) => (
                                <option key={status} value={status}>
                                    {t(`company_control.status_${status}`)}
                                </option>
                            ),
                        )}
                    </select>
                </label>
                <label>
                    <span>{t('company_control.sort')}</span>
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
                            'valuation',
                            'arrears',
                            'occupancy',
                            'collection',
                            'net',
                            'name',
                        ].map((sort) => (
                            <option key={sort} value={sort}>
                                {t(`company_control.sort_${sort}`)}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    <span>{t('company_control.direction')}</span>
                    <select
                        value={draft.direction}
                        onChange={(event) =>
                            setDraft({
                                ...draft,
                                direction: event.currentTarget
                                    .value as FilterState['direction'],
                            })
                        }
                    >
                        <option value="desc">
                            {t('company_control.direction_desc')}
                        </option>
                        <option value="asc">
                            {t('company_control.direction_asc')}
                        </option>
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
                <div className="pmc-company-control-filter-actions">
                    <button type="submit">
                        <i className="bi bi-funnel" aria-hidden="true" />
                        {t('actions.filter')}
                    </button>
                    <button
                        type="button"
                        onClick={() =>
                            router.get(
                                '/company-control',
                                {},
                                {
                                    preserveState: false,
                                    replace: true,
                                },
                            )
                        }
                    >
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
