import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type TenantRecord = {
    id: number;
    profile_type: string;
    national_id?: string | null;
    company_name?: string | null;
    emergency_contact_name?: string | null;
    emergency_contact_phone?: string | null;
    address?: string | null;
    status: string;
    is_showcase?: boolean;
    leases_count?: number;
    active_leases_count?: number;
    open_requests_count?: number;
    user?: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        preferred_locale: 'en' | 'ar';
        status: string;
    } | null;
};

export type TenantInsights = {
    total: number;
    active: number;
    blocked: number;
    companies: number;
    without_active_lease: number;
    missing_emergency: number;
    missing_address: number;
};

export type TenantIndexPageProps = SharedProps & {
    tenants: PaginatedData<TenantRecord>;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    profileTypeOptions: string[];
    statusOptions: string[];
    tenantInsights: TenantInsights;
};
