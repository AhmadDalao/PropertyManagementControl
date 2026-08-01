import type { PropertyPerformance } from '@/modules/dashboard/types';
import type { PaginatedData, SharedProps } from '@/types';

export type PortfolioControlProperty = PropertyPerformance & {
    portfolio_id: number;
    portfolio_code: string;
    portfolio_name_en: string;
    portfolio_name_ar?: string | null;
    is_showcase: boolean;
};

export type PortfolioControlFilters = {
    search: string;
    attention: 'all' | 'risk' | 'watch' | 'on_track';
    portfolio_id: number | null;
    sort: 'attention' | 'arrears' | 'occupancy' | 'collection' | 'net' | 'name';
    per_page: number;
    page: number;
};

export type PortfolioControlSummary = {
    properties: number;
    risk: number;
    occupancy_rate: number;
    collection_rate: number | null;
    arrears: number | null;
    net: number | null;
    currency: string | null;
    currency_count: number;
    currency_totals: PortfolioControlProperty['currency_totals'];
    open_requests: number;
    expiring_leases: number;
};

export type PortfolioControlProps = SharedProps & {
    filters: PortfolioControlFilters;
    counts: Array<{ key: PortfolioControlFilters['attention']; count: number }>;
    summary: PortfolioControlSummary;
    portfolioOptions: Array<{
        id: number;
        code: string;
        name_en: string;
        name_ar?: string | null;
    }>;
    properties: PaginatedData<PortfolioControlProperty>;
};
