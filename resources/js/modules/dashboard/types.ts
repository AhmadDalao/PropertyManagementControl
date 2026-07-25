import type {
    PropertyMapAsset,
    PropertyMapSummary,
} from '@/modules/property-map/types';
import type { SharedProps } from '@/types';

import type {
    CollectionQueueItem,
    LaunchReadinessStatus,
    OperationsFinancial,
    PropertyFocusOption,
    PropertyPerformance,
} from './operations-types';
import type { NextAction } from './shared-types';
import type { TenantDashboardProps } from './tenant-types';

export type {
    CollectionQueueItem,
    LaunchReadinessStatus,
    OperationsFinancial,
    PropertyFocusOption,
    PropertyPerformance,
} from './operations-types';
export type { NextAction } from './shared-types';
export type { TenantDashboardProps } from './tenant-types';

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
    propertyFocus: {
        selected: PropertyFocusOption | null;
        options: PropertyFocusOption[];
        assignment_restricted: boolean;
        has_assignments: boolean;
    };
    stats: OperationsStats;
    financial: OperationsFinancial;
    nextActions: NextAction[];
    charts: { occupancy: Record<string, number> };
    setupChecklist: SetupItem[];
    cmsStatus: {
        published: number;
        draft: number;
        homepage?: string | null;
    } | null;
    readinessStatus: LaunchReadinessStatus | null;
    propertyMap: {
        assets: PropertyMapAsset[];
        summary: PropertyMapSummary;
    };
    expiringLeases: ExpiringLease[];
    arrearsLeases: ArrearsLease[];
    collectionQueue: CollectionQueueItem[];
    propertyPerformance: PropertyPerformance[];
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

export type DashboardPageProps =
    OperationsDashboardProps | TenantDashboardProps;
