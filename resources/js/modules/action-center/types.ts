import type { PaginatedData, PropertyOption, SharedProps } from '@/types';

export type ActionCenterType =
    'collection' | 'maintenance' | 'renewal' | 'move_out' | 'document_expiry';

export type ActionCenterPriority = 'critical' | 'high' | 'normal';

export type ActionCenterFilters = {
    search: string;
    type: 'all' | ActionCenterType;
    priority: 'all' | ActionCenterPriority;
    assignee: string;
    portfolio_id: number | null;
    property_id: number | null;
    per_page: number;
    page: number;
};

export type ActionCenterItem = {
    key: string;
    record_id: number;
    type: ActionCenterType;
    priority: ActionCenterPriority;
    title: string;
    subtitle?: string | null;
    tenant?: string | null;
    asset?: {
        id: number;
        title_en: string;
        title_ar?: string | null;
        code: string;
    } | null;
    portfolio?: {
        id: number;
        name_en: string;
        name_ar?: string | null;
    } | null;
    status: string;
    due_on?: string | null;
    due_state: 'overdue' | 'today' | 'upcoming' | 'unscheduled';
    opened_on?: string | null;
    assigned_to?: {
        id: number;
        name: string;
    } | null;
    work_order?: {
        reference_code: string;
        vendor_name: string;
        scheduled_on?: string | null;
    } | null;
    amount?: number | null;
    currency?: string | null;
    href: string;
    is_showcase: boolean;
};

export type ActionCenterMetricSet = {
    total: number;
    critical: number;
    high: number;
    unassigned: number;
};

export type ActionCenterTypeCount = {
    type: 'all' | ActionCenterType;
    value: number;
    active: boolean;
};

export type ActionCenterPageProps = SharedProps & {
    actionItems: PaginatedData<ActionCenterItem>;
    filters: ActionCenterFilters;
    metrics: ActionCenterMetricSet;
    counts: ActionCenterTypeCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: PropertyOption[];
    assigneeOptions: Array<{ id: number; label: string }>;
};
