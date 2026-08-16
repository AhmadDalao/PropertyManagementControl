import type {
    DetailItem,
    DetailSection,
    RelatedTable,
    ResourceDocument,
    ResourceHeaderProps,
    ResourceProgress,
    ResourceTimelineEntry,
    ResourceWorkflow,
} from '@/components/resource-cycle';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
    PropertyOption,
} from '@/types';

export type LeaseRecord = {
    id: number;
    code: string;
    status: string;
    payment_frequency: string;
    started_at?: string | null;
    ends_at?: string | null;
    signed_at?: string | null;
    currency: string;
    tenant_profile?: {
        user?: { name?: string | null };
    };
    leaseable?: {
        title_en?: string | null;
        title_ar?: string | null;
        code?: string | null;
    };
    total_due: number;
    total_paid: number;
    balance_remaining: number;
    days_remaining?: number | null;
    overdue_count: number;
    next_due_date?: string | null;
    next_due_amount?: number | null;
};

export type LeaseInsights = {
    total: number;
    active: number;
    draft: number;
    unsigned: number;
    expiring_soon: number;
    overdue: number;
    total_due: number;
    total_paid: number;
    balance_remaining: number;
};

export type LeaseIndexPageProps = SharedProps & {
    leases: PaginatedData<LeaseRecord>;
    leaseInsights: LeaseInsights;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: PropertyOption[];
    statusOptions: string[];
    frequencyOptions: string[];
};

export type LeaseRelatedTable = RelatedTable & {
    key: 'installments' | 'payments';
};

export type LeaseDetailPage = {
    mode: 'admin' | 'tenant';
    header: ResourceHeaderProps;
    workflow: ResourceWorkflow;
    progress?: ResourceProgress | null;
    stats: DetailItem[];
    sections: DetailSection[];
    related: LeaseRelatedTable[];
    documents: ResourceDocument[];
    timeline: ResourceTimelineEntry[];
};

export type LeaseDetailPageProps = SharedProps & {
    detailPage: LeaseDetailPage;
};
