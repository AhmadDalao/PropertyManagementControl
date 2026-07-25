import type {
    PaginatedData,
    PropertyOption,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type LeaseMoveOutRecord = {
    id: number;
    lease_id: number;
    code?: string | null;
    lease_status?: string | null;
    status: string;
    state: string;
    move_out_date?: string | null;
    reason: string;
    deposit_disposition: string;
    deposit_deduction_amount: number;
    keys_returned: boolean;
    notice_uploaded: boolean;
    inspection_uploaded: boolean;
    ready: boolean;
    outstanding_amount: number;
    currency: string;
    is_showcase: boolean;
    tenant?: {
        id: number;
        name: string;
        email?: string | null;
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

export type LeaseMoveOutInsights = {
    planned: number;
    attention: number;
    ready: number;
    completed_30_days: number;
};

export type LeaseMoveOutPageProps = SharedProps & {
    moveOuts: PaginatedData<LeaseMoveOutRecord>;
    moveOutInsights: LeaseMoveOutInsights;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: PropertyOption[];
    queueOptions: string[];
    horizonOptions: string[];
};
