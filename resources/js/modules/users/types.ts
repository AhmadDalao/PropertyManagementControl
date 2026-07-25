import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type UserRecord = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    status: string;
    force_password_reset: boolean;
    last_login_at?: string | null;
    is_showcase?: boolean;
    open_assignments_count?: number;
    roles: string[];
    portfolio?: {
        id: number;
        name_en?: string | null;
        name_ar?: string | null;
        code?: string | null;
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

export type UserTableProps = Pick<
    UserIndexPageProps,
    | 'users'
    | 'filters'
    | 'counts'
    | 'portfolioOptions'
    | 'roleOptions'
    | 'statusOptions'
    | 'auth'
    | 'app'
>;

export type ManagerPropertyAssignment = {
    id: number;
    title: string;
    code: string;
    asset_type: string;
    usage_type: string;
    status: string;
    parent?: string | null;
    children_count: number;
    selected: boolean;
    current_manager?: {
        id: number;
        name: string;
    } | null;
};

export type ManagerPropertyAssignmentPageProps = SharedProps & {
    assignmentPage: {
        manager: {
            id: number;
            name: string;
            email: string;
            portfolio?: string | null;
        };
        properties: ManagerPropertyAssignment[];
        selected_ids: number[];
        action: string;
        back_href: string;
    };
};
