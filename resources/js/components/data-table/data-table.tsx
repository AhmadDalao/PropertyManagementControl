import { DesktopRecordTable } from './desktop-record-table';
import { MobileRecordList } from './mobile-record-list';
import { TableHeader } from './table-header';
import { TablePagination } from './table-pagination';
import { TableToolbar } from './table-toolbar';
import type {
    DataTableProps,
    DataTableRow,
    MobileTableConfig,
    TableColumn,
} from './types';
import { useTableQuery } from './use-table-query';

export function DataTable<T extends DataTableRow>({
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
    const query = useTableQuery({ filters, basePath });
    const mobile = mobileCard ?? fallbackMobileCard(columns);

    return (
        <section className="pmc-operations-table">
            <TableHeader
                title={title}
                description={description}
                total={data.total}
                exportHref={exportHref}
            />
            <TableToolbar
                counts={counts}
                filterFields={filterFields}
                draftFilters={query.draftFilters}
                activeFilters={query.activeFilters}
                filtersOpen={query.filtersOpen}
                setDraftFilters={query.setDraftFilters}
                setFiltersOpen={query.setFiltersOpen}
                visit={query.visit}
                reset={query.reset}
                removeFilter={query.removeFilter}
            />
            <DesktopRecordTable
                rows={data.data}
                columns={columns}
                emptyText={emptyText}
                createHref={createHref}
                createLabel={createLabel}
                rowKey={rowKey}
                rowHref={rowHref}
            />
            <MobileRecordList
                rows={data.data}
                config={mobile}
                emptyText={emptyText}
                createHref={createHref}
                createLabel={createLabel}
                rowKey={rowKey}
                rowHref={rowHref}
            />
            <TablePagination data={data} />
        </section>
    );
}

function fallbackMobileCard<T>(
    columns: Array<TableColumn<T>>,
): MobileTableConfig<T> {
    const statusColumn = columns.find((column) =>
        /status|occupancy|account/i.test(column.key),
    );
    const actionColumn = columns.find((column) => column.key === 'actions');
    const metaColumns = columns
        .slice(2)
        .filter(
            (column) =>
                column.key !== statusColumn?.key &&
                column.key !== actionColumn?.key,
        )
        .slice(0, 3);

    return {
        title: (row) => columns[0]?.render(row),
        subtitle: columns[1] ? (row) => columns[1].render(row) : undefined,
        status: statusColumn ? (row) => statusColumn.render(row) : undefined,
        meta: metaColumns.map((column) => ({
            label: column.label,
            value: (row) => column.render(row),
        })),
        actions: actionColumn ? (row) => actionColumn.render(row) : undefined,
    };
}

export const OperationsTable = DataTable;
