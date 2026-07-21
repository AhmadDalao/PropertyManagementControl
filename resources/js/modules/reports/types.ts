import type { SharedProps } from '@/types';

export type ReportMode = 'portfolio' | 'superadmin';
export type ReportTab = 'overview' | 'collections' | 'costs' | 'operations';
export type PresetVisibility = 'global' | 'portfolio' | 'private';

export type ReportFilterValues = {
    date_from: string;
    date_to: string;
    portfolio_id: string;
};

export type ArrearsLease = {
    id: number;
    code: string;
    tenant?: string | null;
    asset?: string | null;
    arrears_amount: number;
    currency: string;
};

export type TopAsset = {
    id: number;
    asset: string;
    revenue: number;
    currency: string;
    lease_count: number;
};

export type PaymentRow = {
    id: number;
    reference: string;
    tenant?: string | null;
    lease?: string | null;
    amount: number;
    currency: string;
    received_on?: string | null;
};

export type ExpenseRow = {
    id: number;
    title: string;
    category: string;
    asset?: string | null;
    amount: number;
    currency: string;
    incurred_on?: string | null;
};

export type MaintenanceRow = {
    id: number;
    title: string;
    asset?: string | null;
    tenant?: string | null;
    status: string;
    priority: string;
    created_at?: string | null;
};

export type ReportPreset = {
    id: number;
    title_en: string;
    title_ar?: string | null;
    visibility: PresetVisibility;
    is_default: boolean;
    can_delete: boolean;
    url: string;
};

export type ReportsPageProps = SharedProps & {
    mode: ReportMode;
    filters: {
        date_from: string;
        date_to: string;
        portfolio_id?: number | null;
    };
    portfolioOptions: Array<{ id: number; name: string }>;
    presetVisibilityOptions: PresetVisibility[];
    summary: {
        revenue: number;
        expenses: number;
        net: number;
        scheduledDue: number;
        scheduledPaid: number;
        collectionRate: number;
        occupancyRate: number;
        arrears: number;
        contractBalance: number;
        activeLeases: number;
        leasesInArrears: number;
        openRequests: number;
        resolvedRequests: number;
    };
    charts: {
        revenueByMonth: Record<string, number>;
        expenseByCategory: Record<string, number>;
        assetMix: Record<string, number>;
        maintenanceByStatus: Record<string, number>;
    };
    arrearsLeases: ArrearsLease[];
    topAssets: TopAsset[];
    recentPayments: PaymentRow[];
    recentExpenses: ExpenseRow[];
    maintenanceBacklog: MaintenanceRow[];
    savedPresets: ReportPreset[];
};

export type ReportRecord = {
    href: string;
    title: string;
    meta: string;
    value: string;
    tone?: 'success' | 'warning' | 'danger';
    status?: string;
};
