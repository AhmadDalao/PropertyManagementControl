import type { PaginatedData, SharedProps } from '@/types';

export type PropertyExplorerFilters = {
    property_id: number | null;
    node_id: number | null;
    search: string;
    asset_type: string;
    occupancy_status: string;
    page: number;
};

export type PropertyExplorerLease = {
    id: number;
    code: string;
    status: string;
    tenant_id?: number | null;
    tenant_name?: string | null;
    tenant_email?: string | null;
    tenant_phone?: string | null;
    started_at?: string | null;
    ends_at?: string | null;
    days_remaining?: number | null;
    total_due: number;
    total_paid: number;
    balance_remaining: number;
    arrears: number;
    currency: string;
    href: string;
    tenant_href?: string | null;
    payments_href: string;
};

export type PropertyExplorerAsset = {
    id: number;
    parent_id?: number | null;
    title_en: string;
    title_ar?: string | null;
    code: string;
    asset_type: string;
    usage_type: string;
    status: string;
    occupancy_status: string;
    rentable: boolean;
    children_count: number;
    parent?: {
        id: number;
        title_en: string;
        title_ar?: string | null;
        code: string;
    } | null;
    owner?: { id: number; name: string } | null;
    manager?: { id: number; name: string } | null;
    lease?: PropertyExplorerLease | null;
    browse_href: string;
    detail_href: string;
};

export type PropertyExplorerSelected = PropertyExplorerAsset & {
    valuation_amount: number;
    currency: string;
    area?: number | null;
    address?: string | null;
    edit_href: string;
    add_child_href: string;
    create_lease_href: string;
    maintenance_href: string;
};

export type PropertyExplorerPayload = {
    filters: PropertyExplorerFilters;
    properties: Array<{
        id: number;
        portfolio_id: number;
        code: string;
        title_en: string;
        title_ar?: string | null;
        portfolio?: string | null;
    }>;
    selected: PropertyExplorerSelected | null;
    breadcrumbs: Array<{
        id: number;
        title_en: string;
        title_ar?: string | null;
        code: string;
        href: string;
    }>;
    metrics: {
        assets?: number;
        floors?: number;
        units?: number;
        rentable?: number;
        occupied?: number;
        vacant?: number;
        maintenance?: number;
        active_leases?: number;
        tenants?: number;
        arrears?: number;
    };
    records: PaginatedData<PropertyExplorerAsset> | null;
    active_lease: PropertyExplorerLease | null;
    modules: {
        tenants: boolean;
        leases: boolean;
        payments: boolean;
        maintenance: boolean;
    };
};

export type PropertyExplorerPageProps = SharedProps & {
    explorer: PropertyExplorerPayload;
};
