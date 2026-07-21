import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type PortfolioRecord = {
    id: number;
    name_en: string;
    name_ar: string;
    code: string;
    status: string;
    city?: string | null;
    country?: string | null;
    default_currency: string;
    users_count?: number;
    assets_count?: number;
    leases_count?: number;
    active_leases_count?: number;
    open_maintenance_count?: number;
    valuation_total?: number | null;
    posted_revenue_total?: number | null;
    posted_expense_total?: number | null;
    module_settings?: Record<string, boolean> | null;
    is_showcase?: boolean;
    owner?: {
        id: number;
        name: string;
        status: string;
    } | null;
};

export type ModuleDefinition = {
    key: string;
    label: string;
    description: string;
};

export type PortfolioInsights = {
    total: number;
    active: number;
    inactive: number;
    archived: number;
    assets: number;
    users: number;
    leases: number;
    active_leases: number;
    open_maintenance: number;
    valuation_total: number | null;
    posted_revenue_total: number | null;
    posted_expense_total: number | null;
    net_total: number | null;
    currency: string;
    currency_count: number;
};

export type PortfolioIndexPageProps = SharedProps & {
    portfolios: PaginatedData<PortfolioRecord>;
    portfolioInsights: PortfolioInsights;
    filters: TableFilters;
    counts: TableCount[];
    canCreate: boolean;
    canUpdate: boolean;
    canArchive: boolean;
    moduleDefinitions: ModuleDefinition[];
    statusOptions: string[];
};
