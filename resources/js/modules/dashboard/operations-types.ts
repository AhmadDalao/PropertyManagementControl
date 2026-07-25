export type OperationsFinancial = {
    scheduledDue: number;
    scheduledPaid: number;
    collectionRate: number;
    revenue: number;
    expenses: number;
    net: number;
    currency: string;
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
};

export type PropertyPerformance = {
    id: number;
    title_en: string;
    title_ar?: string | null;
    code: string;
    currency: string;
    rentable_units: number;
    occupied_units: number;
    active_leases: number;
    expiring_leases: number;
    scheduled_due: number;
    scheduled_paid: number;
    arrears: number;
    collected: number;
    expenses: number;
    net: number;
    open_requests: number;
    occupancy_rate: number;
    collection_rate: number;
    attention_score: number;
    attention: 'risk' | 'watch' | 'on_track';
};
