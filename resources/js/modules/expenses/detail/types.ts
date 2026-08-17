import type {
    DetailItem,
    DetailSection,
    ResourceDocument,
    ResourceHeaderProps,
    ResourceTimelineEntry,
    ResourceWorkflow,
} from '@/components/resource-cycle';
import type { SharedProps } from '@/types';

export type ExpenseDetailTab =
    'overview' | 'financial' | 'evidence' | 'history';

export type ExpenseSectionKey = 'context' | 'financial';

export type ExpenseDetailSection = DetailSection & {
    key: ExpenseSectionKey;
};

export type ExpenseEvidence = {
    enabled: boolean;
    can_upload: boolean;
    upload_url?: string | null;
    documents: ResourceDocument[];
};

export type ExpenseDetailPage = {
    header: ResourceHeaderProps;
    availableTabs: ExpenseDetailTab[];
    workflow: ResourceWorkflow;
    stats: DetailItem[];
    sections: ExpenseDetailSection[];
    evidence: ExpenseEvidence;
    timeline: ResourceTimelineEntry[];
};

export type ExpenseDetailPageProps = SharedProps & {
    detailPage: ExpenseDetailPage;
};
