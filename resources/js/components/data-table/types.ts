import type { ReactNode } from 'react';

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

export type DataTableRow = { id?: number | string };

export type DataTableProps<T extends DataTableRow> = {
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

export type TableVisit = (
    overrides?: Record<string, string | number | null>,
) => void;
