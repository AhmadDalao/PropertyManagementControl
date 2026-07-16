import type { SharedProps } from '@/types';

export type LeaseBalance = {
    id: number;
    code: string;
    tenant?: string | null;
    asset?: string | null;
    ends_at?: string | null;
    days_remaining?: number | null;
    balance_remaining: number;
    currency: string;
};

export type NextAction = {
    label: string;
    description: string;
    href: string;
    icon: string;
};

export type PropertyMapAsset = {
    id: number;
    title: string;
    code: string;
    portfolio?: string | null;
    asset_type: string;
    usage_type: string;
    status: string;
    occupancy_status: string;
    valuation_amount: number;
    currency: string;
    address?: string | null;
    zone?: string | null;
    land_number?: string | null;
    latitude?: number | null;
    longitude?: number | null;
    x: number;
    y: number;
    has_coordinates: boolean;
    has_identity: boolean;
    href: string;
    edit_href: string;
    children_count: number;
    rentable_children_count: number;
    active_leases_count: number;
    open_requests_count: number;
    owner?: string | null;
    manager?: string | null;
};

export type DashboardPageProps = SharedProps & {
    mode: 'tenant' | 'portfolio' | 'superadmin';
    stats: Record<string, number | string | null>;
    nextActions?: NextAction[];
    charts?: {
        occupancy?: Record<string, number>;
        paymentHealth?: Array<{
            code: string;
            tenant?: string | null;
            due: number;
            paid: number;
            remaining: number;
        }>;
        assetMix?: Record<string, number>;
        maintenanceByStatus?: Record<string, number>;
    };
    setupChecklist?: Array<{
        label: string;
        done: boolean;
        href: string;
    }>;
    cmsStatus?: {
        published: number;
        draft: number;
        homepage?: string | null;
    };
    propertyMap?: {
        assets: PropertyMapAsset[];
        summary: {
            mapped: number;
            total: number;
            ready: number;
            needs_position: number;
            needs_identity: number;
            coverage_percent: number;
            zones: string[];
        };
    };
    expiringLeases?: LeaseBalance[];
    arrearsLeases?: LeaseBalance[];
    recentPayments?: Array<{
        id: number;
        amount: number;
        currency: string;
        received_on: string;
        tenant_profile?: { user?: { name: string } };
    }>;
    recentMaintenance?: Array<{
        id: number;
        title: string;
        status: string;
        priority?: string;
        created_at: string;
        asset?: { title_en: string };
    }>;
    tenantPortal?: {
        lease?: {
            id: number;
            code: string;
            days_remaining: number;
            balance_remaining: number;
            total_paid?: number;
            rent_amount?: number;
            currency?: string;
            started_at?: string;
            ends_at?: string;
            leaseable?: { title_en?: string; code?: string } | null;
            contract_url?: string;
            statement_url?: string;
        } | null;
        documents?: Array<{
            id: number;
            title_en: string;
            type: string;
            download_url: string;
        }>;
        payments?: Array<{
            id: number;
            amount: number;
            currency: string;
            received_on: string;
            reference?: string;
            receipt_url?: string;
        }>;
        requests?: Array<{
            id: number;
            title: string;
            status: string;
            created_at: string;
        }>;
    };
};
