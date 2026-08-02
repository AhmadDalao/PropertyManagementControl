import type { PaginatedData, SharedProps } from '@/types';

export type DailyReportStatus =
    'queued' | 'running' | 'completed' | 'failed' | 'pruned';

export type DailyReportFile = {
    available: boolean;
    bytes: number;
    url: string;
};

export type DailyReportRecord = {
    id: number;
    status: DailyReportStatus;
    status_label: string;
    trigger: string;
    trigger_label: string;
    report_date: string;
    scope_label: string;
    portfolio?: { id: number; name: string } | null;
    initiated_by?: string | null;
    item_count: number;
    summary: {
        priority?: {
            total: number;
            critical: number;
            high: number;
            normal: number;
            unassigned: number;
        };
        types?: Array<{ type: string; count: number }>;
        currencies?: Array<{
            currency: string;
            count: number;
            amount: number;
        }>;
    };
    scope: Array<{ label: string; value: string }>;
    failure_summary?: string | null;
    created_at?: string | null;
    started_at?: string | null;
    completed_at?: string | null;
    failed_at?: string | null;
    files: {
        pdf: DailyReportFile;
        docx: DailyReportFile;
        xlsx: DailyReportFile;
    };
    show_url: string;
    action_center_url: string;
    can_prune: boolean;
    prune_url: string;
};

export type DailyReportIndexProps = SharedProps & {
    reports: PaginatedData<DailyReportRecord>;
    filters: {
        status: string;
        portfolio_id?: number | null;
        date_from: string;
        date_to: string;
    };
    summary: {
        completed: number;
        failed: number;
        active: number;
        items: number;
        latest_completed_at?: string | null;
        retention_days: number;
        schedule_time: string;
    };
    portfolioOptions: Array<{ id: number; name: string }>;
    statusOptions: Array<{ value: string; label: string }>;
    canSelectGlobal: boolean;
};

export type DailyReportShowProps = SharedProps & {
    report: DailyReportRecord;
};
