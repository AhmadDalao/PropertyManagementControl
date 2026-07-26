import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type MaintenanceVendorRecord = {
    id: number;
    portfolio_id: number;
    name: string;
    contact_name?: string | null;
    phone?: string | null;
    email?: string | null;
    service_category: string;
    status: string;
    work_orders_count: number;
    active_work_orders_count: number;
    portfolio?: {
        id: number;
        name_en?: string | null;
        name_ar?: string | null;
    } | null;
};

export type MaintenanceVendorIndexProps = SharedProps & {
    vendors: PaginatedData<MaintenanceVendorRecord>;
    vendorInsights: {
        total: number;
        active: number;
        inactive: number;
        active_work_orders: number;
    };
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    categoryOptions: string[];
    statusOptions: string[];
};
