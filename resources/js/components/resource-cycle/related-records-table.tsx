import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { RelatedCell, RelatedTable } from './types';

export function RelatedRecordsTable({ table }: { table: RelatedTable }) {
    const { t, text } = useTranslator();

    return (
        <article className="pmc-card p-4 pmc-related-table-card">
            <header className="pmc-related-table-head">
                <div>
                    <div className="pmc-kicker mb-2">{text(table.title)}</div>
                    {table.description ? (
                        <p>{text(table.description)}</p>
                    ) : null}
                </div>
                {table.actionHref ? (
                    <Link
                        href={table.actionHref}
                        className="btn btn-light btn-sm"
                    >
                        {table.actionLabel
                            ? text(table.actionLabel)
                            : t('actions.open')}
                    </Link>
                ) : null}
            </header>
            {table.rows.length > 0 ? (
                <div className="pmc-table-scroll">
                    <table className="pmc-data-table table">
                        <thead>
                            <tr>
                                {table.columns.map((column) => (
                                    <th key={column}>{text(column)}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {table.rows.map((row, index) => (
                                <tr key={index}>
                                    {table.columns.map((column) => {
                                        const value = row[column] ?? '-';

                                        return (
                                            <td
                                                key={column}
                                                data-label={text(column)}
                                            >
                                                {isRelatedCellLink(value) ? (
                                                    <Link href={value.href}>
                                                        {value.label}
                                                    </Link>
                                                ) : (
                                                    value
                                                )}
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <p className="pmc-empty-inline">
                    {table.emptyText
                        ? text(table.emptyText)
                        : t('resource.no_related_records')}
                </p>
            )}
        </article>
    );
}

function isRelatedCellLink(
    value: RelatedCell,
): value is { label: string; href: string } {
    return Boolean(
        value &&
        typeof value === 'object' &&
        !Array.isArray(value) &&
        'href' in value &&
        'label' in value,
    );
}
