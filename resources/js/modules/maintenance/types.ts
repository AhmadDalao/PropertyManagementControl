import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type MaintenanceRecord = {
    id: number;
    title: string;
    status: string;
    category: string;
    priority: string;
    created_at: string;
    due_at?: string | null;
    is_overdue: boolean;
    assigned_to?: { id: number; name: string } | null;
    asset?: {
        id: number;
        title_en: string;
        title_ar?: string | null;
        code?: string | null;
    } | null;
    tenant_profile?: {
        user?: { name?: string | null; email?: string | null };
    };
    expense_total: number;
    expense_count: number;
};

export type MaintenanceInsights = {
    total: number;
    open: number;
    in_progress: number;
    resolved: number;
    cancelled: number;
    urgent: number;
    overdue: number;
    unassigned: number;
    posted_expenses: number;
};

export type MaintenanceIndexPageProps = SharedProps & {
    mode: 'tenant' | 'manager';
    requests: PaginatedData<MaintenanceRecord>;
    maintenanceInsights: MaintenanceInsights;
    filters: TableFilters;
    counts: TableCount[];
    categoryOptions: string[];
    priorityOptions: string[];
    statusOptions: string[];
};
