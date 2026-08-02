import type {
    PaginatedData,
    PropertyOption,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type WorkOrderRecord = {
    id: number;
    reference_code: string;
    status: string;
    scheduled_at?: string | null;
    completed_at?: string | null;
    estimated_amount?: number | null;
    final_amount?: number | null;
    currency: string;
    scope: string;
    tenant_access_required: boolean;
    is_overdue: boolean;
    request?: {
        id: number;
        title: string;
        category: string;
        priority: string;
        status: string;
    } | null;
    asset?: {
        id: number;
        title_en: string;
        title_ar?: string | null;
        code?: string | null;
    } | null;
    tenant?: { name: string } | null;
    vendor: { id?: number | null; name: string };
    assigned_to?: { id: number; name: string } | null;
};

export type WorkOrderIndexProps = SharedProps & {
    workOrders: PaginatedData<WorkOrderRecord>;
    workOrderInsights: {
        total: number;
        active: number;
        unscheduled: number;
        overdue: number;
        completed: number;
        unassigned: number;
        tenant_access: number;
    };
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: PropertyOption[];
    vendorOptions: Array<{ id: number; name: string }>;
    assigneeOptions: Array<{ id: number; name: string }>;
    statusOptions: string[];
    scheduleOptions: string[];
    tenantAccessOptions: string[];
};
