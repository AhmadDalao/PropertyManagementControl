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
    follow_up: CollectionFollowUpSummary;
    lease?: {
        id: number;
        code: string;
        status: string;
    } | null;
    portfolio?: {
        id: number;
        name_en: string;
        name_ar: string;
        code: string;
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

export type CollectionFollowUpSummary = {
    id?: number;
    state:
        'untracked' | 'due' | 'promised' | 'broken' | 'scheduled' | 'settled';
    history_count: number;
    contact_method?: string | null;
    outcome?: string | null;
    contacted_at?: string | null;
    promised_amount?: number | null;
    promised_on?: string | null;
    next_follow_up_on?: string | null;
    note?: string | null;
    assigned_to?: { id: number; name: string } | null;
    recorded_by?: { id: number; name: string } | null;
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
    untracked_overdue_count: number;
    follow_up_due_count: number;
    broken_promises_count: number;
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
    followUpOptions: string[];
};

export type CollectionFollowUpRecord = {
    id?: number;
    contact_method?: string | null;
    outcome?: string | null;
    contacted_at?: string | null;
    promised_amount?: number | null;
    promised_on?: string | null;
    next_follow_up_on?: string | null;
    note?: string | null;
    state?: CollectionFollowUpSummary['state'];
    history_count?: number;
    assigned_to?: { id: number; name: string } | null;
    recorded_by?: { id: number; name: string } | null;
};

export type CollectionFollowUpPageData = {
    installment: {
        id: number;
        label: string;
        line_type: string;
        due_date?: string | null;
        amount_due: number;
        amount_paid: number;
        outstanding_amount: number;
        currency: string;
        status: string;
        days_overdue: number;
        is_showcase: boolean;
    };
    lease: { id: number; code: string; status: string };
    tenant: { name: string; email?: string | null; phone?: string | null };
    asset?: {
        id: number;
        title_en: string;
        title_ar?: string | null;
        code: string;
    } | null;
    property?: {
        id: number;
        title_en: string;
        title_ar?: string | null;
        code: string;
    } | null;
    latest_follow_up: CollectionFollowUpRecord;
    history: CollectionFollowUpRecord[];
    history_truncated: boolean;
    assignee_options: Array<{ id: number; label: string }>;
    contact_method_options: Array<{ value: string; label: string }>;
    outcome_options: Array<{ value: string; label: string }>;
    initial_values: {
        contact_method: string;
        outcome: string;
        contacted_at: string;
        assigned_to_user_id: number | string;
        next_follow_up_on: string;
        promised_amount: number;
        promised_on: string;
        note: string;
    };
    can_record: boolean;
    links: {
        back: string;
        store: string;
        lease: string;
        payment: string;
        statement: string;
    };
};

export type CollectionFollowUpPageProps = SharedProps & {
    collection: CollectionFollowUpPageData;
};
