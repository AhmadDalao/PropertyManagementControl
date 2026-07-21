import type { Dispatch, FormEvent, SetStateAction } from 'react';

import { useTranslator } from '@/lib/i18n';
import type { TableCount } from '@/types';

import { filterLabel, PAGE_SIZES } from './table-utils';
import type { TableFilterField, TableVisit } from './types';

type TableToolbarProps = {
    counts: TableCount[];
    filterFields: TableFilterField[];
    draftFilters: Record<string, string>;
    activeFilters: Array<[string, string]>;
    filtersOpen: boolean;
    setDraftFilters: Dispatch<SetStateAction<Record<string, string>>>;
    setFiltersOpen: Dispatch<SetStateAction<boolean>>;
    visit: TableVisit;
    reset: () => void;
    removeFilter: (name: string) => void;
};

export function TableToolbar({
    counts,
    filterFields,
    draftFilters,
    activeFilters,
    filtersOpen,
    setDraftFilters,
    setFiltersOpen,
    visit,
    reset,
    removeFilter,
}: TableToolbarProps) {
    const { t, text } = useTranslator();
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        visit();
    };

    return (
        <>
            {counts.length > 0 ? (
                <div
                    className="pmc-filter-chips"
                    aria-label={t('table.quick_filters', 'Quick filters')}
                >
                    {counts.map((count) => (
                        <button
                            key={`${count.label}-${count.value}`}
                            type="button"
                            className={`pmc-filter-chip ${count.active ? 'active' : ''}`}
                            onClick={() => visit(count.filter ?? {})}
                        >
                            <span>{text(count.label)}</span>
                            <strong>{count.value}</strong>
                        </button>
                    ))}
                </div>
            ) : null}

            <form className="pmc-table-toolbar" onSubmit={submit}>
                <div className="pmc-table-primary-tools">
                    <label className="pmc-table-search">
                        <span className="visually-hidden">
                            {t('actions.search', 'Search')}
                        </span>
                        <i className="bi bi-search" />
                        <input
                            type="search"
                            className="form-control"
                            value={draftFilters.search ?? ''}
                            placeholder={t('table.search', 'Search records...')}
                            onChange={(event) =>
                                setDraftFilters((current) => ({
                                    ...current,
                                    search: event.currentTarget.value,
                                }))
                            }
                        />
                    </label>
                    <button
                        type="button"
                        className="pmc-mobile-filter-trigger"
                        aria-expanded={filtersOpen}
                        onClick={() => setFiltersOpen((open) => !open)}
                    >
                        <i className="bi bi-sliders2" />
                        {t('table.filters', 'Filters')}
                        {activeFilters.length > 0 ? (
                            <strong>{activeFilters.length}</strong>
                        ) : null}
                    </button>
                </div>

                <div
                    className={`pmc-table-filter-panel ${filtersOpen ? 'is-open' : ''}`}
                >
                    <label className="pmc-table-page-size">
                        <span>{t('table.show', 'Show')}</span>
                        <select
                            className="form-select"
                            value={draftFilters.per_page ?? '10'}
                            onChange={(event) =>
                                visit({
                                    per_page: event.currentTarget.value,
                                })
                            }
                        >
                            {PAGE_SIZES.map((size) => (
                                <option key={size} value={size}>
                                    {size}
                                </option>
                            ))}
                        </select>
                        <span>{t('table.entries', 'entries')}</span>
                    </label>

                    {filterFields.map((field) => (
                        <label key={field.name} className="pmc-table-filter">
                            <span>{text(field.label)}</span>
                            {field.type === 'date' || field.type === 'text' ? (
                                <input
                                    type={field.type}
                                    className="form-control"
                                    value={draftFilters[field.name] ?? ''}
                                    onChange={(event) =>
                                        setDraftFilters((current) => ({
                                            ...current,
                                            [field.name]:
                                                event.currentTarget.value,
                                        }))
                                    }
                                />
                            ) : (
                                <select
                                    className="form-select"
                                    value={draftFilters[field.name] ?? 'all'}
                                    onChange={(event) =>
                                        visit({
                                            [field.name]:
                                                event.currentTarget.value,
                                        })
                                    }
                                >
                                    {(field.options ?? []).map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {text(option.label)}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </label>
                    ))}

                    <div className="pmc-table-actions">
                        <button className="btn btn-primary" type="submit">
                            <i className="bi bi-funnel" />
                            {t('table.filter', 'Filter')}
                        </button>
                        <button
                            className="btn btn-outline-secondary"
                            type="button"
                            onClick={reset}
                        >
                            <i className="bi bi-arrow-counterclockwise" />
                            {t('table.reset', 'Reset')}
                        </button>
                    </div>
                </div>
            </form>

            {activeFilters.length > 0 ? (
                <div className="pmc-active-filters">
                    <span>{t('table.active_filters', 'Active filters')}</span>
                    {activeFilters.map(([name, value]) => (
                        <button
                            key={name}
                            type="button"
                            onClick={() => removeFilter(name)}
                        >
                            {text(filterLabel(name, filterFields))}:{' '}
                            {text(value)}
                            <i className="bi bi-x" />
                        </button>
                    ))}
                    <button type="button" onClick={reset}>
                        {t('table.clear_filters', 'Clear filters')}
                    </button>
                </div>
            ) : null}
        </>
    );
}
