import type {
    DetailItem,
    DetailSection,
    ResourceHeaderProps,
    ResourceTimelineEntry,
    ResourceWorkflow,
} from '@/components/resource-cycle';
import type { SharedProps } from '@/types';

export type DocumentDetailTab = 'overview' | 'access' | 'validity' | 'history';

export type DocumentSectionKey =
    'identity' | 'ownership' | 'access' | 'validity';

export type DocumentDetailSection = DetailSection & {
    key: DocumentSectionKey;
};

export type DocumentReplacement = {
    can_upload: boolean;
    upload_url?: string | null;
    title: string;
    description: string;
    action_label: string;
    unavailable: string;
};

export type DocumentDetailPage = {
    header: ResourceHeaderProps;
    availableTabs: DocumentDetailTab[];
    workflow: ResourceWorkflow;
    stats: DetailItem[];
    sections: DocumentDetailSection[];
    replacement: DocumentReplacement;
    timeline: ResourceTimelineEntry[];
};

export type DocumentDetailPageProps = SharedProps & {
    detailPage: DocumentDetailPage;
};
