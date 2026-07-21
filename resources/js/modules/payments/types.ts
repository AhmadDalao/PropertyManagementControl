import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type PaymentRecord = {
    id: number;
    reference?: string | null;
    amount: number;
    currency: string;
    received_on?: string | null;
    status: string;
    type: string;
    method: string;
    allocated_amount: number;
    unallocated_amount: number;
    allocation_count: number;
    receipt_url: string;
    tenant_profile?: {
        user?: { name?: string | null; email?: string | null };
    };
    lease?: {
        code?: string | null;
        leaseable?: {
            title_en?: string | null;
            title_ar?: string | null;
            code?: string | null;
        };
    };
};

export type PaymentInsights = {
    total: number;
    posted_count: number;
    pending_count: number;
    void_count: number;
    posted_amount: number;
    pending_amount: number;
    void_amount: number;
    allocated_amount: number;
    unallocated_amount: number;
    received_this_month: number;
};

export type PaymentIndexPageProps = SharedProps & {
    payments: PaginatedData<PaymentRecord>;
    paymentInsights: PaymentInsights;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    statusOptions: string[];
    typeOptions: string[];
    methodOptions: string[];
};
