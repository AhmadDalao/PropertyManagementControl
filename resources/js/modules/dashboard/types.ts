import type {
    PropertyMapAsset,
    PropertyMapSummary,
} from '@/modules/property-map/types';
import type { SharedProps } from '@/types';

import type {
    CollectionQueueItem,
    LaunchReadinessStatus,
    OperationsFinancial,
    PlatformComposition,
    PropertyFocusOption,
    PropertyPerformance,
} from './operations-types';
import type { NextAction } from './shared-types';
import type { TenantDashboardProps } from './tenant-types';

export type {
    CollectionQueueItem,
    LaunchReadinessStatus,
    OperationsFinancial,
    PlatformComposition,
    PropertyFocusOption,
    PropertyPerformance,
} from './operations-types';
export type { OperationsWorkSection } from './operations-types';
export type { NextAction } from './shared-types';
export type { TenantDashboardProps } from './tenant-types';

export type SetupItem = {
    key: string;
    label: string;
    description: string;
    icon: string;
    done: boolean;
    href: string;
};

export type DashboardSetupTarget = {
    id: number;
    code: string;
    name: string;
    href: string;
    completed: number;
    total: number;
    next: {
        label: string;
        description: string;
        href: string;
        action_label: string;
        icon: string;
    } | null;
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

export type MoveOutQueueItem = {
    id: number;
    lease_id: number;
    code?: string | null;
    tenant?: string | null;
    asset_en?: string | null;
    asset_ar?: string | null;
    move_out_date?: string | null;
    state: 'scheduled' | 'due_today' | 'overdue' | 'ready';
};

export type OperationsStats = {
    totalUsers: number;
    totalPortfolios: number;
    totalAssets: number;
    totalValue: number | null;
    valuationCurrency: string | null;
    valuationTotals: Array<{ currency: string; amount: number }>;
    activeLeases: number;
    monthlyRevenue: number | null;
    monthlyExpenses: number | null;
    openRequests: number;
    arrears: number | null;
    hasArrears: boolean;
    vacantUnits: number;
};

export type OperationsDashboardProps = SharedProps & {
    mode: 'portfolio' | 'superadmin';
    propertyFocus: {
        selected: PropertyFocusOption | null;
        property_count: number;
        assignment_restricted: boolean;
        has_assignments: boolean;
    };
    setupTarget: DashboardSetupTarget | null;
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
    platformComposition: PlatformComposition | null;
    propertyMap: {
        assets: PropertyMapAsset[];
        summary: PropertyMapSummary;
    };
    expiringLeases: ExpiringLease[];
    arrearsLeases: ArrearsLease[];
    collectionQueue: CollectionQueueItem[];
    moveOutQueue: {
        attention: number;
        ready: number;
        items: MoveOutQueueItem[];
    };
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
