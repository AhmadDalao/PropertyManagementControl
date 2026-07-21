import type {
    PropertyMapAsset,
    PropertyMapSummary,
} from '@/modules/property-map/types';
import type { SharedProps } from '@/types';

export type LeaseBalance = {
    id: number;
    code: string;
    tenant?: string | null;
    asset?: string | null;
    ends_at?: string | null;
    days_remaining?: number | null;
    balance_remaining: number;
    arrears_amount?: number;
    currency: string;
};

export type NextAction = {
    label: string;
    description: string;
    href: string;
    icon: string;
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
        summary: PropertyMapSummary;
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
        asset?: { title_en: string; title_ar?: string | null };
    }>;
    tenantPortal?: {
        lease?: {
            id: number;
            code: string;
            days_remaining: number;
            balance_remaining: number;
            due_now: number;
            overdue: number;
            next_due_date?: string | null;
            total_paid?: number;
            rent_amount?: number;
            currency?: string;
            started_at?: string;
            ends_at?: string;
            leaseable?: {
                title_en?: string;
                title_ar?: string;
                code?: string;
            } | null;
            contract_url?: string;
            statement_url?: string;
        } | null;
        documents?: Array<{
            id: number;
            title_en: string;
            title_ar?: string;
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
