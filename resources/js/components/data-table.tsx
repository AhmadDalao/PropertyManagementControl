import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

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
};

const pageSizes = [10, 25, 50, 100];

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
}: DataTableProps<T>) {
    const [draftFilters, setDraftFilters] = useState<Record<string, string>>(
        stringifyFilters(filters),
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

    return (
        <section className="pmc-operations-table">
            <div className="pmc-operations-head">
                <div>
                    <div className="pmc-table-heading">
                        <span className="pmc-table-icon">
                            <i className="bi bi-table" />
                        </span>
                        <strong>{title}</strong>
                        <span className="pmc-table-count">{data.total}</span>
                    </div>
                    {description ? (
                        <p className="pmc-table-copy">{description}</p>
                    ) : null}
                </div>
                {exportHref ? (
                    <a
                        className="btn btn-outline-secondary btn-sm pmc-export-button"
                        href={exportHref}
                    >
                        <i className="bi bi-download me-2" />
                        Export CSV
                    </a>
                ) : null}
            </div>

            {counts.length > 0 ? (
                <div className="pmc-filter-chips" aria-label="Quick filters">
                    {counts.map((count) => (
                        <button
                            key={`${count.label}-${count.value}`}
                            type="button"
                            className={`pmc-filter-chip ${count.active ? 'active' : ''}`}
                            onClick={() => visit(count.filter ?? {})}
                        >
                            <span>{count.label}</span>
                            <strong>{count.value}</strong>
                        </button>
                    ))}
                </div>
            ) : null}

            <form className="pmc-table-toolbar" onSubmit={submit}>
                <label className="pmc-table-page-size">
                    <span>Show</span>
                    <select
                        className="form-select"
                        value={draftFilters.per_page ?? '25'}
                        onChange={(event) => {
                            setDraftFilters((current) => ({
                                ...current,
                                per_page: event.currentTarget.value,
                            }));
                            visit({ per_page: event.currentTarget.value });
                        }}
                    >
                        {pageSizes.map((size) => (
                            <option key={size} value={size}>
                                {size}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="pmc-table-search">
                    <span className="visually-hidden">Search</span>
                    <i className="bi bi-search" />
                    <input
                        type="search"
                        className="form-control"
                        value={draftFilters.search ?? ''}
                        placeholder="Search table..."
                        onChange={(event) =>
                            setDraftFilters((current) => ({
                                ...current,
                                search: event.currentTarget.value,
                            }))
                        }
                    />
                </label>

                {filterFields.map((field) => (
                    <label key={field.name} className="pmc-table-filter">
                        <span>{field.label}</span>
                        {field.type === 'date' || field.type === 'text' ? (
                            <input
                                type={field.type}
                                className="form-control"
                                value={draftFilters[field.name] ?? ''}
                                onChange={(event) =>
                                    setDraftFilters((current) => ({
                                        ...current,
                                        [field.name]: event.currentTarget.value,
                                    }))
                                }
                                onBlur={() => visit()}
                            />
                        ) : (
                            <select
                                className="form-select"
                                value={draftFilters[field.name] ?? 'all'}
                                onChange={(event) => {
                                    setDraftFilters((current) => ({
                                        ...current,
                                        [field.name]: event.currentTarget.value,
                                    }));
                                    visit({
                                        [field.name]: event.currentTarget.value,
                                    });
                                }}
                            >
                                {(field.options ?? []).map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        )}
                    </label>
                ))}

                <div className="pmc-table-actions">
                    <button className="btn btn-primary btn-sm" type="submit">
                        <i className="bi bi-funnel me-2" />
                        Filter
                    </button>
                    <button
                        className="btn btn-outline-secondary btn-sm"
                        type="button"
                        onClick={() => {
                            setDraftFilters({ per_page: '25' });
                            router.get(
                                basePath,
                                {},
                                { preserveScroll: true, replace: true },
                            );
                        }}
                    >
                        <i className="bi bi-arrow-counterclockwise me-2" />
                        Reset
                    </button>
                </div>
            </form>

            <div className="pmc-table-scroll">
                <table className="pmc-data-table table">
                    <thead>
                        <tr>
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    className={column.headerClassName}
                                >
                                    {column.label}
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
                                >
                                    {columns.map((column) => (
                                        <td
                                            key={column.key}
                                            data-label={column.label}
                                            className={column.className}
                                        >
                                            {column.render(row)}
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
                                    <div className="pmc-empty-state">
                                        <i className="bi bi-search" />
                                        <strong>No matching records</strong>
                                        <span>{emptyText}</span>
                                    </div>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <div className="pmc-table-footer">
                <p className="pmc-table-results">
                    Showing {data.from ?? 0} to {data.to ?? 0} of {data.total}{' '}
                    entries
                </p>
                <div className="pmc-table-pagination">
                    {data.links.map((link, index) => (
                        <button
                            key={`${link.label}-${index}`}
                            type="button"
                            className={`pmc-page-button ${link.active ? 'active' : ''}`}
                            disabled={!link.url}
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
            </div>
        </section>
    );
}

export const OperationsTable = DataTable;

export function exportUrl(path: string, filters: TableFilters): string {
    const query = new URLSearchParams(
        cleanFilters(stringifyFilters(filters)),
    ).toString();

    return query ? `${path}?${query}` : path;
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
