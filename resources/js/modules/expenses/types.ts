import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type ExpenseRecord = {
    id: number;
    portfolio_id: number;
    asset_id?: number | null;
    maintenance_request_id?: number | null;
    title: string;
    category: string;
    status: string;
    vendor_name?: string | null;
    amount: number;
    currency: string;
    incurred_on?: string | null;
    asset?: {
        id: number;
        title_en?: string | null;
        title_ar?: string | null;
        code?: string | null;
    } | null;
    maintenance_request?: {
        id: number;
        title?: string | null;
        status?: string | null;
        priority?: string | null;
    } | null;
};

export type ExpenseInsights = {
    total: number;
    posted_count: number;
    pending_count: number;
    void_count: number;
    posted_amount: number | null;
    pending_amount: number | null;
    void_amount: number | null;
    maintenance_amount: number | null;
    linked_to_assets: number;
    linked_to_maintenance: number;
    unlinked_count: number;
    vendors: number;
    posted_this_month: number | null;
    currency: string | null;
    currency_count: number;
};

export type ExpenseIndexPageProps = SharedProps & {
    expenses: PaginatedData<ExpenseRecord>;
    expenseInsights: ExpenseInsights;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    categoryOptions: string[];
    statusOptions: string[];
};
