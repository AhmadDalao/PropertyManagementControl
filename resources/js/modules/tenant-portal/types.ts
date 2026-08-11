import type { PaginatedData, SharedProps } from '@/types';

export type TenantLeaseOption = {
    id: number;
    code: string;
    status: string;
    started_at: string | null;
    ends_at: string | null;
    asset_title_en?: string | null;
    asset_title_ar?: string | null;
};

export type TenantLease = TenantLeaseOption & {
    payment_frequency: string;
    signed_at: string | null;
    rent_amount: number;
    deposit_amount: number;
    currency: string;
    billing_day?: number | null;
    total_due: number;
    total_paid: number;
    balance_remaining: number;
    due_now: number;
    overdue: number;
    days_remaining: number | null;
    next_due_date: string | null;
    installment_count: number;
    asset: {
        id: number;
        code?: string | null;
        title_en: string;
        title_ar?: string | null;
        address?: string | null;
        address_ar?: string | null;
        asset_type: string;
        usage_type: string;
    } | null;
    contract_url: string;
    contract_word_url: string;
    statement_url: string;
    detail_url: string;
};

export type TenantInstallment = {
    id: number;
    sequence: number;
    line_type: string;
    label: string;
    due_date: string | null;
    amount_due: number;
    amount_paid: number;
    remaining: number;
    status: string;
};

export type TenantPayment = {
    id: number;
    reference?: string | null;
    amount: number;
    currency: string;
    received_on: string | null;
    status: string;
    type: string;
    method: string;
    allocated_amount: number;
    unallocated_amount: number;
    receipt_url: string;
    lease: {
        id: number;
        code: string;
        status: string;
        leaseable?: {
            title_en: string;
            title_ar?: string | null;
            code?: string | null;
        } | null;
    } | null;
};

export type TenantDocument = {
    id: number;
    type: string;
    title_en: string;
    title_ar?: string | null;
    original_name: string;
    file_size?: number | null;
    issued_on: string | null;
    expires_on: string | null;
    expiry_status: string;
    download_url: string;
    attachment: { type: string; label: string; url?: string | null };
};

export type TenantLeasePageProps = SharedProps & {
    leases: TenantLeaseOption[];
    lease: TenantLease | null;
    schedule: PaginatedData<TenantInstallment>;
    documents: TenantDocument[];
};

export type TenantPaymentsPageProps = SharedProps & {
    filters: TenantPortalFilters;
    payments: PaginatedData<TenantPayment>;
    counts: Record<string, number>;
    financials: Array<{
        currency: string;
        scheduled: number;
        paid: number;
        outstanding: number;
        overdue: number;
    }>;
    leases: TenantLeaseOption[];
};

export type TenantDocumentsPageProps = SharedProps & {
    filters: TenantPortalFilters;
    documents: PaginatedData<TenantDocument>;
    counts: Record<string, number>;
    types: string[];
    leases: TenantLeaseOption[];
};

export type TenantPortalFilters = {
    search: string;
    status: string;
    type: string;
    lease_id: number | null;
    date_from: string;
    date_to: string;
    per_page: number;
};
