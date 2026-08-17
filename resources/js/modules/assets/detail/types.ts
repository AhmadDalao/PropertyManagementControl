import type {
    DetailItem,
    DetailSection,
    RelatedTable,
    ResourceDocument,
    ResourceHeaderProps,
    ResourceSpotlight,
    ResourceTimelineEntry,
    ResourceWorkflow,
} from '@/components/resource-cycle';
import type { SharedProps } from '@/types';

export type AssetDetailTab =
    | 'overview'
    | 'structure'
    | 'leasing'
    | 'financial'
    | 'service'
    | 'documents'
    | 'history';

export type AssetSectionKey =
    'profile' | 'ownership' | 'financial' | 'active_rental';

export type AssetRelatedKey =
    | 'rentable_spaces'
    | 'children'
    | 'leases'
    | 'collections'
    | 'maintenance'
    | 'expenses';

export type AssetDetailSection = DetailSection & { key: AssetSectionKey };
export type AssetRelatedTable = RelatedTable & { key: AssetRelatedKey };

export type AssetDetailPage = {
    header: ResourceHeaderProps;
    availableTabs: AssetDetailTab[];
    spotlight: ResourceSpotlight;
    workflow: ResourceWorkflow;
    stats: DetailItem[];
    sections: AssetDetailSection[];
    related: AssetRelatedTable[];
    documents: ResourceDocument[];
    timeline: ResourceTimelineEntry[];
};

export type AssetDetailPageProps = SharedProps & {
    detailPage: AssetDetailPage;
};
