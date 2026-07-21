import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type UserRecord = {
    id: number;
    portfolio_id?: number | null;
    name: string;
    email: string;
    phone?: string | null;
    preferred_locale: 'en' | 'ar';
    status: string;
    force_password_reset: boolean;
    last_login_at?: string | null;
    is_showcase?: boolean;
    portfolios_owned_count?: number;
    open_assignments_count?: number;
    roles?: Array<{ id: number; name: string }>;
    tenant_profile?: {
        id: number;
        status: string;
        profile_type: string;
    } | null;
    portfolio?: {
        id: number;
        name_en?: string | null;
        name_ar?: string | null;
        code?: string | null;
        status: string;
    } | null;
};

export type UserInsights = {
    total: number;
    active: number;
    suspended: number;
    temporary_passwords: number;
    tenants_without_profile: number;
};

export type UserIndexPageProps = SharedProps & {
    users: PaginatedData<UserRecord>;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    roleOptions: string[];
    statusOptions: string[];
    userInsights: UserInsights;
};
