export type OperationsCurrencyPosition = {
    currency: string;
    scheduledDue: number;
    scheduledPaid: number;
    collectionRate: number;
    revenue: number;
    expenses: number;
    net: number;
    arrears: number;
};

export type OperationsFinancial = {
    scheduledDue: number | null;
    scheduledPaid: number | null;
    collectionRate: number | null;
    revenue: number | null;
    expenses: number | null;
    net: number | null;
    arrears: number | null;
    currency: string | null;
    currencyCount: number;
    currencyTotals: OperationsCurrencyPosition[];
    hasArrears: boolean;
};

export type LaunchReadinessStatus = {
    status: 'ready' | 'attention' | 'blocked';
    automatic_ready: number;
    automatic_attention: number;
    automatic_blocked: number;
    evidence_remaining: number;
    operational_portfolios: number;
    showcase_portfolios: number;
    showcase_assets: number;
    showcase_users: number;
};

export type PlatformComposition = {
    portfolios: {
        live_active: number;
        live_inactive: number;
        live_archived: number;
        showcase: number;
    };
    properties: {
        live: number;
        showcase: number;
        asset_records: number;
    };
    accounts: {
        live_active: number;
        live_inactive: number;
        showcase: number;
        roles: {
            superadmins: number;
            owners: number;
            managers: number;
            tenants: number;
        };
    };
};

export type PlatformActivity = {
    id: number;
    event: string;
    event_label: string;
    subject_type: string | null;
    subject_type_label: string;
    subject_label: string;
    subject_url: string;
    causer_label: string;
    changed_count: number;
    created_at: string | null;
    portfolio: {
        id: number;
        name: string;
        url: string;
    } | null;
};

export type PropertyFocusOption = {
    id: number;
    code: string;
    title_en: string;
    title_ar?: string | null;
};

export type CollectionQueueItem = {
    id: number;
    lease_id: number | null;
    lease_code: string | null;
    tenant?: string | null;
    asset_en?: string | null;
    asset_ar?: string | null;
    due_date?: string | null;
    outstanding_amount: number;
    days_overdue: number;
    currency: string;
    follow_up_state: 'untracked' | 'due' | 'promised' | 'broken' | 'scheduled';
    next_follow_up_on?: string | null;
    promised_on?: string | null;
    assigned_to?: string | null;
};

export type OperationsWorkSection =
    'actions' | 'collections' | 'maintenance' | 'move_outs';

export type PropertyPerformance = {
    id: number;
    title_en: string;
    title_ar?: string | null;
    code: string;
    is_showcase: boolean;
    currency: string | null;
    currency_count: number;
    currency_totals: Array<{
        currency: string;
        scheduled_due: number;
        scheduled_paid: number;
        collection_rate: number;
        arrears: number;
        collected: number;
        expenses: number;
        net: number;
    }>;
    rentable_units: number;
    occupied_units: number;
    active_leases: number;
    expiring_leases: number;
    scheduled_due: number | null;
    scheduled_paid: number | null;
    arrears: number | null;
    collected: number | null;
    expenses: number | null;
    net: number | null;
    open_requests: number;
    occupancy_rate: number;
    collection_rate: number | null;
    attention_score: number;
    attention: 'risk' | 'watch' | 'on_track';
};
