import type { SharedProps } from '@/types';

export type ReportMode = 'portfolio' | 'superadmin';
export type ReportTab =
    'library' | 'overview' | 'collections' | 'costs' | 'operations';
export type PresetVisibility = 'global' | 'portfolio' | 'private';

export type ReportLibraryCard = {
    key: string;
    icon: string;
    title: string;
    description: string;
    openLabel: string;
    openHref: string;
    downloads: Array<{ label: string; href: string }>;
};

export type ReportLibraryGroup = {
    key: string;
    title: string;
    description: string;
    cards: ReportLibraryCard[];
};

export type ReportFilterValues = {
    date_from: string;
    date_to: string;
    portfolio_id: string;
    property_id: string;
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

export type OperationalJournalEvent = {
    key: string;
    type:
        | 'payment'
        | 'expense'
        | 'lease'
        | 'maintenance_opened'
        | 'maintenance_resolved'
        | 'document';
    type_label: string;
    title: string;
    subtitle: string;
    occurred_at?: string | null;
    actor: string;
    href: string;
    amount?: number | null;
    currency?: string | null;
    direction: 'income' | 'outflow' | 'none';
    icon: string;
    tone: 'success' | 'warning' | 'danger' | 'info';
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
        property_id?: number | null;
    };
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: Array<{
        id: number;
        portfolio_id: number;
        name: string;
    }>;
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
        openCollectionCount: number;
        untrackedOverdueCount: number;
        followUpDueCount: number;
        brokenPromisesCount: number;
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
    journalSummary: {
        totalEvents: number;
        newLeases: number;
        serviceOpened: number;
        serviceResolved: number;
        documentsAdded: number;
    };
    operationalJournal: OperationalJournalEvent[];
    savedPresets: ReportPreset[];
    reportLibrary: ReportLibraryGroup[];
};

export type OwnerStatementPageProps = Omit<
    ReportsPageProps,
    | 'portfolioOptions'
    | 'propertyOptions'
    | 'presetVisibilityOptions'
    | 'savedPresets'
    | 'reportLibrary'
> & {
    statement: {
        portfolio: { en: string; ar: string };
        property: { en: string; ar: string };
        prepared_for: string;
        generated_at: string;
    };
};

export type ReportRecord = {
    href: string;
    title: string;
    meta: string;
    value: string;
    tone?: 'success' | 'warning' | 'danger';
    status?: string;
};
