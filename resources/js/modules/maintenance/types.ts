import type {
    DetailItem,
    DetailSection,
    RelatedTable,
    ResourceDocument,
    ResourceFormShellProps,
    ResourceHeaderProps,
    ResourceProgress,
    ResourceTimelineEntry,
    ResourceWorkflow,
} from '@/components/resource-cycle';
import type {
    PaginatedData,
    PropertyOption,
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
    awaiting_confirmation: boolean;
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
    pending_confirmation: number;
    cancelled: number;
    urgent: number;
    overdue: number;
    unassigned: number;
    posted_expenses: number;
};

export type MaintenanceIndexPageProps = SharedProps & {
    mode: 'tenant' | 'manager';
    financialsEnabled: boolean;
    requests: PaginatedData<MaintenanceRecord>;
    maintenanceInsights: MaintenanceInsights;
    filters: TableFilters;
    counts: TableCount[];
    categoryOptions: string[];
    priorityOptions: string[];
    statusOptions: string[];
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: PropertyOption[];
};

export type MaintenanceTableProps = Pick<
    MaintenanceIndexPageProps,
    | 'mode'
    | 'financialsEnabled'
    | 'requests'
    | 'filters'
    | 'counts'
    | 'categoryOptions'
    | 'priorityOptions'
    | 'statusOptions'
    | 'portfolioOptions'
    | 'propertyOptions'
    | 'auth'
    | 'app'
>;

export type MaintenanceRelatedTable = RelatedTable & {
    key: 'work-orders' | 'updates' | 'expenses';
};

export type MaintenanceDetailPage = {
    mode: 'tenant' | 'manager';
    header: ResourceHeaderProps;
    requestContext: DetailItem[];
    serviceContext: DetailItem[];
    stats: DetailItem[];
    sections: DetailSection[];
    progress: ResourceProgress;
    workflow: ResourceWorkflow;
    related: MaintenanceRelatedTable[];
    documents: ResourceDocument[];
    timeline: ResourceTimelineEntry[];
};

export type MaintenanceRequestFormPageProps = SharedProps & {
    formPage: ResourceFormShellProps;
};

export type MaintenanceDetailPageProps = SharedProps & {
    detailPage: MaintenanceDetailPage;
};

export type MaintenanceTriagePageProps = SharedProps & {
    formPage: ResourceFormShellProps;
    detailPage: MaintenanceDetailPage;
};
