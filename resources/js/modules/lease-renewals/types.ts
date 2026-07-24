import type {
    PaginatedData,
    PropertyOption,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type LeaseRenewalRecord = {
    id: number;
    code: string;
    status: string;
    payment_frequency: string;
    started_at?: string | null;
    ends_at?: string | null;
    days_remaining?: number | null;
    renewal_notice_days: number;
    contact_due_on?: string | null;
    notice_due: boolean;
    renewal_state: string;
    rent_amount: number;
    outstanding_amount: number;
    overdue_installments_count: number;
    currency: string;
    is_showcase: boolean;
    tenant?: {
        id: number;
        name: string;
        email?: string | null;
        phone?: string | null;
    } | null;
    asset?: {
        id: number;
        title_en: string;
        title_ar: string;
        code: string;
    } | null;
    property?: {
        id: number;
        title_en: string;
        title_ar: string;
        code: string;
    } | null;
    renewal?: {
        id: number;
        code: string;
        status: string;
        started_at?: string | null;
        ends_at?: string | null;
    } | null;
};

export type LeaseRenewalInsights = {
    action_required: number;
    ending_30_days: number;
    renewals_prepared: number;
    expired_unresolved: number;
};

export type LeaseRenewalPageProps = SharedProps & {
    renewals: PaginatedData<LeaseRenewalRecord>;
    renewalInsights: LeaseRenewalInsights;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: PropertyOption[];
    queueOptions: string[];
    horizonOptions: string[];
    leaseStatusOptions: string[];
};
