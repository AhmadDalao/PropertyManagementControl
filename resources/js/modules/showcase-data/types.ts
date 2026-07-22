import type { PaginatedData, SharedProps } from '@/types';

export type ShowcaseDataset = {
    id: number;
    key: string;
    name: string;
    status: string;
    target_properties: number;
    generated_properties: number;
    progress_percent: number;
    counts: Record<string, number>;
    failure_details?: string | null;
    initiated_by?: string | null;
    started_at?: string | null;
    completed_at?: string | null;
    purged_at?: string | null;
    can_retry: boolean;
    can_purge: boolean;
};

export type ShowcaseSummary = {
    datasets: number;
    active: number;
    complete: number;
    failed: number;
    live_buildings: number;
};

export type ShowcaseDataPageProps = SharedProps & {
    datasets: PaginatedData<ShowcaseDataset>;
    summary: ShowcaseSummary;
    targets: Record<string, number>;
    canGenerate: boolean;
    legacyCandidates: number;
};
