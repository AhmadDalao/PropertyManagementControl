import type { SharedProps } from '@/types';

export type TenantStatementTab =
    'overview' | 'installments' | 'payments' | 'maintenance' | 'documents';

export type TenantStatementFinancial = {
    currency: string;
    scheduled_due: number;
    scheduled_paid: number;
    received: number;
    contract_balance: number;
    overdue: number;
};

export type TenantStatementLease = {
    id: number;
    code: string;
    asset_en?: string | null;
    asset_ar?: string | null;
    status: string;
    started_at?: string | null;
    ends_at?: string | null;
    total_due: number;
    total_paid: number;
    balance: number;
    overdue: number;
    currency: string;
    href: string;
};

export type TenantStatementPageProps = SharedProps & {
    filters: { date_from: string; date_to: string };
    tenant: {
        id: number;
        name: string;
        email?: string | null;
        phone?: string | null;
        profile_type: string;
        status: string;
        national_id?: string | null;
        company_name?: string | null;
        is_showcase: boolean;
        portfolio: {
            id: number;
            code?: string | null;
            name_en?: string | null;
            name_ar?: string | null;
        };
        back_url: string;
    };
    statement: {
        prepared_for: string;
        generated_at: string;
        lease_count: number;
        active_lease_count: number;
        open_maintenance_count: number;
        document_count: number;
        financials: TenantStatementFinancial[];
    };
    leases: TenantStatementLease[];
    installments: Array<{
        id: number;
        lease_code?: string | null;
        due_date?: string | null;
        label?: string | null;
        status: string;
        amount_due: number;
        amount_paid: number;
        remaining: number;
        currency: string;
        href?: string | null;
    }>;
    payments: Array<{
        id: number;
        reference: string;
        lease_code?: string | null;
        received_on?: string | null;
        method: string;
        status: string;
        amount: number;
        currency: string;
        href: string;
    }>;
    maintenance: Array<{
        id: number;
        title: string;
        asset_en?: string | null;
        asset_ar?: string | null;
        status: string;
        priority: string;
        requested_at?: string | null;
        href: string;
    }>;
    documents: Array<{
        id: number;
        title_en: string;
        title_ar?: string | null;
        type: string;
        created_at?: string | null;
        download_url: string;
    }>;
    counts: {
        installments: number;
        payments: number;
        maintenance: number;
        documents: number;
    };
    limits: { rows: number };
};
