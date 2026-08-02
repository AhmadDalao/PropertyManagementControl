import type { SharedProps } from '@/types';

export type ReportMode = 'portfolio' | 'superadmin';
export type ReportTab =
    'library' | 'overview' | 'collections' | 'costs' | 'operations';
export type PresetVisibility = 'global' | 'portfolio' | 'private';
export type ReportPeriod =
    'custom' | 'this_month' | 'last_month' | 'last_30_days' | 'year_to_date';

export type CurrencyPosition = {
    currency: string;
    revenue: number;
    expenses: number;
    net: number;
    scheduledDue: number;
    scheduledPaid: number;
    collectionRate: number;
    arrears: number;
    contractBalance: number;
};

export type CurrencyBreakdown = {
    label: string;
    currency: string;
    amount: number;
};

export type ReportComparisonMetric = {
    key:
        | 'collected'
        | 'expenses'
        | 'net_position'
        | 'scheduled_due'
        | 'collection_health'
        | 'maintenance_opened'
        | 'maintenance_resolved';
    format: 'money' | 'number' | 'percent';
    current: number;
    previous: number;
    change: number | null;
    changeKind: 'percent' | 'points';
    trend: 'up' | 'down' | 'flat' | 'new';
};

export type ReportComparison = {
    period: {
        date_from: string;
        date_to: string;
    };
    currencyPositions: Array<{
        currency: string;
        metrics: ReportComparisonMetric[];
    }>;
    serviceMetrics: ReportComparisonMetric[];
};

export type ReportLibraryCard = {
    key: string;
    icon: string;
    title: string;
    description: string;
    openLabel: string;
    openHref: string;
    scopeLabels: string[];
    downloads: Array<{ label: string; href: string }>;
};

export type ReportLibraryGroup = {
    key: string;
    title: string;
    description: string;
    cards: ReportLibraryCard[];
};

export type ReportFilterValues = {
    period: ReportPeriod;
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
    can_edit: boolean;
    can_duplicate: boolean;
    period: ReportPeriod;
    date_from: string;
    date_to: string;
    scope_label: string;
    filters: {
        period: ReportPeriod;
        date_from: string;
        date_to: string;
        portfolio_id?: number | null;
        property_id?: number | null;
    };
    url: string;
    export_url: string;
    edit_url: string;
};

export type ReportDataProps = SharedProps & {
    mode: ReportMode;
    filters: {
        period: ReportPeriod;
        date_from: string;
        date_to: string;
        portfolio_id?: number | null;
        property_id?: number | null;
    };
    summary: {
        currency: string | null;
        currencyCount: number;
        currencyTotals: CurrencyPosition[];
        revenue: number | null;
        expenses: number | null;
        net: number | null;
        scheduledDue: number | null;
        scheduledPaid: number | null;
        collectionRate: number | null;
        occupancyRate: number;
        arrears: number | null;
        contractBalance: number | null;
        activeLeases: number;
        leasesInArrears: number;
        openRequests: number;
        resolvedRequests: number;
        openCollectionCount: number;
        untrackedOverdueCount: number;
        followUpDueCount: number;
        brokenPromisesCount: number;
    };
    comparison: ReportComparison;
    charts: {
        revenueByMonth: CurrencyBreakdown[];
        expenseByCategory: CurrencyBreakdown[];
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
};

export type ReportsPageProps = ReportDataProps & {
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: Array<{
        id: number;
        portfolio_id: number;
        name: string;
    }>;
    reportLibrary: ReportLibraryGroup[];
};

export type SavedReportsPageProps = SharedProps & {
    savedPresets: ReportPreset[];
};

export type SavedReportFormData = {
    resource: 'portfolio-report';
    title_en: string;
    title_ar: string;
    visibility: PresetVisibility;
    is_default: boolean;
    filters_json: Record<string, string>;
};

export type SavedReportFormPageProps = SharedProps & {
    mode: 'create' | 'edit';
    preset: ReportPreset | null;
    filters: ReportDataProps['filters'];
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: Array<{
        id: number;
        portfolio_id: number;
        name: string;
    }>;
    visibilityOptions: PresetVisibility[];
};

export type OwnerStatementPageProps = ReportDataProps & {
    statement: {
        portfolio: { en: string; ar: string };
        property: { en: string; ar: string };
        prepared_for: string;
        generated_at: string;
    };
};

export type OwnerStatementTab =
    'overview' | 'comparison' | 'arrears' | 'payments' | 'maintenance';

export type PropertyReportTab =
    'overview' | 'collections' | 'costs' | 'operations';

export type PropertyReportPageProps = ReportDataProps & {
    property: {
        id: number;
        code: string;
        title_en: string;
        title_ar?: string | null;
        address_en?: string | null;
        address_ar?: string | null;
        status: string;
        usage_type: string;
        valuation_amount: number;
        currency: string;
        is_showcase: boolean;
        portfolio: {
            id: number;
            code?: string | null;
            name_en?: string | null;
            name_ar?: string | null;
        };
        owner?: { id: number; name: string } | null;
        manager?: { id: number; name: string } | null;
        structure: {
            records: number;
            floors: number;
            units: number;
            rentable: number;
            occupied: number;
            vacant: number;
            active_tenants: number;
        };
        links: {
            asset: string;
            explorer: string;
            action_center: string;
            payments: string;
            expenses: string;
            leases: string;
            maintenance: string;
            documents: string;
        };
        downloads: {
            xlsx: string;
            pdf: string;
            docx: string;
        };
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
