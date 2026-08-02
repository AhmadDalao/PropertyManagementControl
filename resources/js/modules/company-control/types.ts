import type { PaginatedData, SharedProps } from '@/types';

export type CompanyCurrencyPosition = {
    currency: string;
    scheduled_due: number;
    scheduled_paid: number;
    arrears: number;
    collected: number;
    expenses: number;
    collection_rate: number;
    net: number;
};

export type CompanyPortfolio = {
    id: number;
    code: string;
    name_en: string;
    name_ar: string;
    status: string;
    is_showcase: boolean;
    default_currency: string;
    owner: { id: number; name: string } | null;
    accounts: {
        active: number;
        owners: number;
        managers: number;
        tenants: number;
    };
    valuation_totals: Array<{ currency: string; amount: number }>;
    readiness: {
        score: number;
        status: 'ready' | 'attention' | 'blocked';
        blocked: number;
        attention: number;
        assignment_gaps: number;
        missing_terms: number;
    };
    properties: number;
    risk_properties: number;
    watch_properties: number;
    rentable_units: number;
    occupied_units: number;
    occupancy_rate: number;
    active_leases: number;
    expiring_leases: number;
    open_requests: number;
    currency_totals: CompanyCurrencyPosition[];
    attention: 'risk' | 'watch' | 'on_track';
};

export type CompanyControlFilters = {
    search: string;
    data_source: 'live' | 'showcase' | 'all';
    status: 'active' | 'inactive' | 'archived' | 'all';
    attention: 'all' | CompanyPortfolio['attention'];
    sort:
        | 'attention'
        | 'valuation'
        | 'arrears'
        | 'occupancy'
        | 'collection'
        | 'net'
        | 'name';
    direction: 'asc' | 'desc';
    per_page: number;
    page: number;
};

export type CompanyControlSummary = {
    portfolios: number;
    needs_action: number;
    properties: number;
    active_accounts: number;
    occupancy_rate: number;
    open_requests: number;
    valuation_totals: Array<{ currency: string; amount: number }>;
    currency_totals: CompanyCurrencyPosition[];
};

export type CompanyControlProps = SharedProps & {
    filters: CompanyControlFilters;
    counts: Array<{
        key: CompanyControlFilters['attention'];
        count: number;
    }>;
    summary: CompanyControlSummary;
    portfolios: PaginatedData<CompanyPortfolio>;
};
