import type { PaginatedData, SharedProps } from '@/types';

export type SystemBackupStatus =
    'queued' | 'running' | 'completed' | 'failed' | 'pruned';

export type SystemBackupRecord = {
    id: number;
    status: SystemBackupStatus;
    status_label: string;
    trigger: string;
    trigger_label: string;
    initiated_by?: string | null;
    database_bytes: number;
    documents_bytes: number;
    archive_bytes: number;
    table_count: number;
    database_row_count: number;
    document_count: number;
    archive_sha256?: string | null;
    failure_summary?: string | null;
    created_at?: string | null;
    started_at?: string | null;
    completed_at?: string | null;
    failed_at?: string | null;
    archive_available: boolean;
    can_download: boolean;
    can_prune: boolean;
    download_url: string;
    prune_url: string;
};

export type SystemBackupPageProps = SharedProps & {
    backups: PaginatedData<SystemBackupRecord>;
    filters: {
        status: string;
    };
    summary: {
        completed: number;
        failed: number;
        active: number;
        stored_bytes: number;
        latest_completed_at?: string | null;
        retention_count: number;
    };
    statusOptions: Array<{
        value: string;
        label: string;
    }>;
};
