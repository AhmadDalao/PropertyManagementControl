import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

import { replaceTokens, useTranslator } from '@/lib/i18n';
import type { PaginatedData, TableCount, TableFilters } from '@/types';

export type TableColumn<T> = {
    key: string;
    label: string;
    render: (row: T) => ReactNode;
    className?: string;
    headerClassName?: string;
};

export type TableFilterField = {
    name: string;
    label: string;
    type?: 'select' | 'date' | 'text';
    options?: Array<{ label: string; value: string | number }>;
};

export type MobileTableConfig<T> = {
    title: (row: T) => ReactNode;
    subtitle?: (row: T) => ReactNode;
    status?: (row: T) => ReactNode;
    meta?: Array<{
        label: string;
        value: (row: T) => ReactNode;
    }>;
    actions?: (row: T) => ReactNode;
};

type DataTableProps<T> = {
    title: string;
    description?: string;
    data: PaginatedData<T>;
    columns: Array<TableColumn<T>>;
    filters: TableFilters;
    basePath: string;
    exportHref?: string;
    counts?: TableCount[];
    filterFields?: TableFilterField[];
    emptyText?: string;
    rowKey?: (row: T) => string | number;
    rowHref?: (row: T) => string;
    createHref?: string;
    createLabel?: string;
    mobileCard?: MobileTableConfig<T>;
};

const pageSizes = [10, 25, 50, 100];
const ignoredActiveFilters = new Set([
    'page',
    'per_page',
    'sort',
    'direction',
    'search',
]);

export function DataTable<T extends { id?: number | string }>({
    title,
    description,
    data,
    columns,
    filters,
    basePath,
    exportHref,
    counts = [],
    filterFields = [],
    emptyText = 'No records match this search.',
    rowKey,
    rowHref,
    createHref,
    createLabel = 'Create',
    mobileCard,
}: DataTableProps<T>) {
    const { t, text } = useTranslator();
    const [draftFilters, setDraftFilters] = useState<Record<string, string>>(
        stringifyFilters(filters),
    );
    const [filtersOpen, setFiltersOpen] = useState(false);
    const activeFilters = Object.entries(draftFilters).filter(
        ([key, value]) =>
            !ignoredActiveFilters.has(key) && value !== '' && value !== 'all',
    );

    const visit = (overrides: Record<string, string | number | null> = {}) => {
        const nextFilters = {
            ...draftFilters,
            ...stringifyFilters(overrides),
        };

        if (!Object.prototype.hasOwnProperty.call(overrides, 'page')) {
            nextFilters.page = '1';
        }

        setDraftFilters(nextFilters);
        router.get(basePath, cleanFilters(nextFilters), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        visit();
    };

    const reset = () => {
        const resetFilters = { per_page: '10' };
        setDraftFilters(resetFilters);
        router.get(basePath, {}, { preserveScroll: true, replace: true });
    };

    const removeFilter = (name: string) => {
        visit({ [name]: 'all' });
    };

    const statusColumn = columns.find((column) =>
        /status|occupancy|account/i.test(column.key),
    );
    const actionColumn = columns.find((column) => column.key === 'actions');
    const mobileMetaColumns = columns
        .slice(2)
        .filter(
            (column) =>
                column.key !== statusColumn?.key &&
                column.key !== actionColumn?.key,
        )
        .slice(0, 3);
    const fallbackMobileCard: MobileTableConfig<T> = {
        title: (row) => columns[0]?.render(row),
        subtitle: columns[1] ? (row) => columns[1].render(row) : undefined,
        status: statusColumn ? (row) => statusColumn.render(row) : undefined,
        meta: mobileMetaColumns.map((column) => ({
            label: column.label,
            value: (row) => column.render(row),
        })),
        actions: actionColumn ? (row) => actionColumn.render(row) : undefined,
    };
    const mobile = mobileCard ?? fallbackMobileCard;
    const previousLink = data.links.at(0);
    const nextLink = data.links.at(-1);

    return (
        <section className="pmc-operations-table">
            <div className="pmc-operations-head">
                <div>
                    <div className="pmc-table-heading">
                        <span className="pmc-table-icon">
                            <i className="bi bi-view-list" />
                        </span>
                        <strong>{text(title)}</strong>
                        <span className="pmc-table-count">{data.total}</span>
                    </div>
                    {description ? (
                        <p className="pmc-table-copy">{text(description)}</p>
                    ) : null}
                </div>
                {exportHref ? (
                    <a
                        className="btn btn-outline-secondary pmc-export-button"
                        href={exportHref}
                    >
                        <i className="bi bi-file-earmark-excel" />
                        <span>
                            {t('actions.export_xlsx', 'Export Excel (.xlsx)')}
                        </span>
                    </a>
                ) : null}
            </div>

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
                            {pageSizes.map((size) => (
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

            <div className="pmc-table-scroll">
                <table className="pmc-data-table table">
                    <thead>
                        <tr>
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    className={column.headerClassName}
                                >
                                    {text(column.label)}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {data.data.length > 0 ? (
                            data.data.map((row, index) => (
                                <tr
                                    key={
                                        rowKey ? rowKey(row) : (row.id ?? index)
                                    }
                                    className={
                                        rowHref
                                            ? 'pmc-clickable-row'
                                            : undefined
                                    }
                                >
                                    {columns.map((column, columnIndex) => (
                                        <td
                                            key={column.key}
                                            className={column.className}
                                        >
                                            {columnIndex === 0 ? (
                                                <div className="pmc-table-primary-record">
                                                    {rowHref ? (
                                                        <Link
                                                            href={rowHref(row)}
                                                            className="pmc-table-row-link"
                                                        >
                                                            {column.render(row)}
                                                        </Link>
                                                    ) : (
                                                        column.render(row)
                                                    )}
                                                    {isShowcaseRow(row) ? (
                                                        <ShowcaseBadge
                                                            label={t(
                                                                'showcase.badge',
                                                            )}
                                                        />
                                                    ) : null}
                                                </div>
                                            ) : (
                                                column.render(row)
                                            )}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td
                                    className="pmc-empty-cell"
                                    colSpan={columns.length}
                                >
                                    <TableEmpty
                                        title={t(
                                            'table.no_matches',
                                            'No matching records',
                                        )}
                                        message={text(emptyText)}
                                        createHref={createHref}
                                        createLabel={text(createLabel)}
                                    />
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <div className="pmc-mobile-record-list">
                {data.data.length > 0 ? (
                    data.data.map((row, index) => (
                        <article
                            className="pmc-mobile-record-card"
                            key={rowKey ? rowKey(row) : (row.id ?? index)}
                        >
                            <div className="pmc-mobile-record-head">
                                <div>
                                    {rowHref ? (
                                        <Link href={rowHref(row)}>
                                            {mobile.title(row)}
                                        </Link>
                                    ) : (
                                        mobile.title(row)
                                    )}
                                    {mobile.subtitle ? (
                                        <small>{mobile.subtitle(row)}</small>
                                    ) : null}
                                    {isShowcaseRow(row) ? (
                                        <ShowcaseBadge
                                            label={t('showcase.badge')}
                                        />
                                    ) : null}
                                </div>
                                {mobile.status ? mobile.status(row) : null}
                            </div>
                            {mobile.meta && mobile.meta.length > 0 ? (
                                <dl>
                                    {mobile.meta.slice(0, 3).map((item) => (
                                        <div key={item.label}>
                                            <dt>{text(item.label)}</dt>
                                            <dd>{item.value(row)}</dd>
                                        </div>
                                    ))}
                                </dl>
                            ) : null}
                            <div className="pmc-mobile-record-footer">
                                {rowHref ? (
                                    <Link
                                        href={rowHref(row)}
                                        className="pmc-record-open"
                                    >
                                        {t('actions.open', 'Open')}
                                        <i className="bi bi-arrow-up-right" />
                                    </Link>
                                ) : (
                                    <span />
                                )}
                                {mobile.actions ? (
                                    <details className="pmc-mobile-action-menu">
                                        <summary
                                            aria-label={t(
                                                'common.more_actions',
                                                'More actions',
                                            )}
                                        >
                                            <i className="bi bi-three-dots" />
                                        </summary>
                                        <div>{mobile.actions(row)}</div>
                                    </details>
                                ) : null}
                            </div>
                        </article>
                    ))
                ) : (
                    <TableEmpty
                        title={t('table.no_matches', 'No matching records')}
                        message={text(emptyText)}
                        createHref={createHref}
                        createLabel={text(createLabel)}
                    />
                )}
            </div>

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
                            onClick={() =>
                                link.url &&
                                router.visit(link.url, {
                                    preserveScroll: true,
                                    preserveState: true,
                                })
                            }
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
                        onClick={() =>
                            previousLink?.url &&
                            router.visit(previousLink.url, {
                                preserveScroll: true,
                                preserveState: true,
                            })
                        }
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
                        onClick={() =>
                            nextLink?.url &&
                            router.visit(nextLink.url, {
                                preserveScroll: true,
                                preserveState: true,
                            })
                        }
                    >
                        <span>{t('pagination.next', 'Next')}</span>
                        <i className="bi bi-chevron-right" />
                    </button>
                </div>
            </div>
        </section>
    );
}

export const OperationsTable = DataTable;

function ShowcaseBadge({ label }: { label: string }) {
    return (
        <span className="pmc-table-showcase-badge">
            <i className="bi bi-stars" />
            {label}
        </span>
    );
}

function isShowcaseRow(row: unknown): boolean {
    return Boolean(
        typeof row === 'object' &&
        row !== null &&
        'is_showcase' in row &&
        row.is_showcase,
    );
}

export function exportUrl(path: string, filters: TableFilters): string {
    const query = new URLSearchParams(
        cleanFilters(stringifyFilters(filters)),
    ).toString();

    return query ? `${path}?${query}` : path;
}

function TableEmpty({
    title,
    message,
    createHref,
    createLabel,
}: {
    title: string;
    message: string;
    createHref?: string;
    createLabel: string;
}) {
    return (
        <div className="pmc-empty-state">
            <i className="bi bi-search" />
            <strong>{title}</strong>
            <span>{message}</span>
            {createHref ? (
                <Link href={createHref} className="btn btn-primary btn-sm">
                    <i className="bi bi-plus-lg" />
                    {createLabel}
                </Link>
            ) : null}
        </div>
    );
}

function filterLabel(name: string, fields: TableFilterField[]): string {
    return (
        fields.find((field) => field.name === name)?.label ??
        name.replaceAll('_', ' ')
    );
}

function interpolate(
    value: string,
    replacements: Record<string, string | number>,
): string {
    return replaceTokens(value, replacements);
}

function stringifyFilters(filters: TableFilters): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).map(([key, value]) => [
            key,
            value === null || value === undefined ? '' : String(value),
        ]),
    );
}

function cleanFilters(filters: Record<string, string>): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([key, value]) =>
                value !== '' &&
                value !== 'all' &&
                !(key === 'page' && value === '1'),
        ),
    );
}

function cleanPaginationLabel(label: string): string {
    const cleanLabel = label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .trim();

    return cleanLabel || label;
}
