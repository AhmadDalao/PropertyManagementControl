import type {
    DetailItem,
    DetailSection,
    RelatedTable,
    ResourceDocument,
    ResourceHeaderProps,
    ResourceTimelineEntry,
    ResourceWorkflow,
} from '@/components/resource-cycle';
import type { SharedProps } from '@/types';

export type UserDetailTab =
    'overview' | 'access' | 'properties' | 'workload' | 'documents' | 'history';

export type UserSectionKey = 'identity' | 'access';
export type UserRelatedKey = 'properties' | 'workload';

export type UserDetailSection = DetailSection & {
    key: UserSectionKey;
};

export type UserRelatedTable = RelatedTable & {
    key: UserRelatedKey;
};

export type UserDetailPage = {
    header: ResourceHeaderProps;
    availableTabs: UserDetailTab[];
    workflow: ResourceWorkflow;
    stats: DetailItem[];
    sections: UserDetailSection[];
    related: UserRelatedTable[];
    documents: ResourceDocument[];
    timeline: ResourceTimelineEntry[];
};

export type UserDetailPageProps = SharedProps & {
    detailPage: UserDetailPage;
};
