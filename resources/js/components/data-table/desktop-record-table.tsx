import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import { ShowcaseBadge } from './showcase-badge';
import { TableEmpty } from './table-empty';
import { isShowcaseRow } from './table-utils';
import type { DataTableRow, TableColumn } from './types';

type DesktopRecordTableProps<T extends DataTableRow> = {
    rows: T[];
    columns: Array<TableColumn<T>>;
    emptyText: string;
    createHref?: string;
    createLabel: string;
    rowKey?: (row: T) => string | number;
    rowHref?: (row: T) => string;
};

export function DesktopRecordTable<T extends DataTableRow>({
    rows,
    columns,
    emptyText,
    createHref,
    createLabel,
    rowKey,
    rowHref,
}: DesktopRecordTableProps<T>) {
    const { t, text } = useTranslator();

    return (
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
                    {rows.length > 0 ? (
                        rows.map((row, index) => (
                            <tr
                                key={rowKey ? rowKey(row) : (row.id ?? index)}
                                className={
                                    rowHref ? 'pmc-clickable-row' : undefined
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
    );
}
