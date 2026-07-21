import type {
    PropertyMapAsset,
    PropertyMapSummary,
} from '@/modules/property-map/types';
import type { SharedProps } from '@/types';

export type NextAction = {
    label: string;
    description: string;
    href: string;
    icon: string;
};

export type SetupItem = {
    label: string;
    done: boolean;
    href: string;
};

export type ExpiringLease = {
    id: number;
    code: string;
    tenant?: string | null;
    asset?: string | null;
    ends_at?: string | null;
    days_remaining?: number | null;
    balance_remaining: number;
    currency: string;
};

export type ArrearsLease = {
    id: number;
    code: string;
    tenant?: string | null;
    asset?: string | null;
    arrears_amount: number;
    currency: string;
};

export type OperationsStats = {
    totalUsers: number;
    totalPortfolios: number;
    totalAssets: number;
    totalValue: number;
    activeLeases: number;
    monthlyRevenue: number;
    monthlyExpenses: number;
    openRequests: number;
    arrears: number;
    vacantUnits: number;
};

export type OperationsDashboardProps = SharedProps & {
    mode: 'portfolio' | 'superadmin';
    stats: OperationsStats;
    nextActions: NextAction[];
    charts: { occupancy: Record<string, number> };
    setupChecklist: SetupItem[];
    cmsStatus: {
        published: number;
        draft: number;
        homepage?: string | null;
    } | null;
    propertyMap: {
        assets: PropertyMapAsset[];
        summary: PropertyMapSummary;
    };
    expiringLeases: ExpiringLease[];
    arrearsLeases: ArrearsLease[];
    recentPayments: Array<{
        id: number;
        amount: number;
        currency: string;
        received_on: string | null;
        tenant_profile?: { user?: { name?: string | null } };
    }>;
    recentMaintenance: Array<{
        id: number;
        title: string;
        status: string;
        priority?: string;
        created_at: string | null;
        asset?: {
            title_en: string;
            title_ar?: string | null;
        } | null;
    }>;
};

export type TenantDashboardProps = SharedProps & {
    mode: 'tenant';
    stats: {
        leaseCode: string | null;
        daysLeft: number | null;
        amountLeft: number;
        dueNow: number;
        overdue: number;
        paidAmount: number;
        maintenanceRequests: number;
    };
    nextActions: NextAction[];
    tenantPortal: {
        lease: {
            id: number;
            code: string;
            days_remaining: number | null;
            balance_remaining: number;
            due_now: number;
            overdue: number;
            next_due_date?: string | null;
            total_paid: number;
            rent_amount: number;
            currency: string;
            started_at?: string | null;
            ends_at?: string | null;
            leaseable?: {
                title_en?: string;
                title_ar?: string;
                code?: string;
            } | null;
            contract_url: string;
            statement_url: string;
        } | null;
        documents: Array<{
            id: number;
            title_en: string;
            title_ar?: string | null;
            type: string;
            download_url: string;
        }>;
        payments: Array<{
            id: number;
            amount: number;
            currency: string;
            received_on: string | null;
            reference?: string | null;
            receipt_url: string;
        }>;
        requests: Array<{
            id: number;
            title: string;
            status: string;
            created_at: string | null;
        }>;
    };
};

export type DashboardPageProps =
    OperationsDashboardProps | TenantDashboardProps;
