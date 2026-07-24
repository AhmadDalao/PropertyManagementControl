import type {
    PaginatedData,
    PropertyOption,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type RentCollectionRecord = {
    id: number;
    sequence: number;
    line_type: string;
    label: string;
    period_start?: string | null;
    period_end?: string | null;
    due_date?: string | null;
    amount_due: number;
    amount_paid: number;
    outstanding_amount: number;
    status: string;
    days_overdue: number;
    days_until_due: number;
    currency: string;
    is_showcase: boolean;
    lease?: {
        id: number;
        code: string;
        status: string;
    } | null;
    tenant?: {
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
};

export type RentCollectionInsights = {
    open_count: number;
    overdue_count: number;
    outstanding_amount: number;
    overdue_amount: number;
    due_next_30_amount: number;
    scheduled_this_month: number;
    paid_this_month: number;
    collection_rate: number;
    currency: string;
    mixed_currencies: boolean;
};

export type RentCollectionPageProps = SharedProps & {
    installments: PaginatedData<RentCollectionRecord>;
    collectionInsights: RentCollectionInsights;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: PropertyOption[];
    statusOptions: string[];
    lineTypeOptions: string[];
};
