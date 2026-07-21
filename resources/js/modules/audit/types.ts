import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type AuditRecord = {
    id: number;
    event: string;
    event_label: string;
    description: string;
    subject_type?: string | null;
    subject_type_label: string;
    subject_label: string;
    subject_url?: string | null;
    causer_label: string;
    changed_keys: string[];
    changed_count: number;
    created_at?: string | null;
};

export type AuditInsights = {
    total: number;
    created: number;
    updated: number;
    deleted: number;
};

export type AuditIndexPageProps = SharedProps & {
    activities: PaginatedData<AuditRecord>;
    auditInsights: AuditInsights;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    subjectTypeOptions: Array<{ label: string; value: string }>;
    causerOptions: Array<{ id: number; name: string }>;
};
