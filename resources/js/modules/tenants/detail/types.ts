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

export type TenantDetailTab =
    'overview' | 'rental' | 'payments' | 'service' | 'documents' | 'history';

export type TenantSectionKey = 'profile' | 'rental' | 'financial';
export type TenantRelatedKey = 'leases' | 'payments' | 'maintenance';

export type TenantDetailSection = DetailSection & { key: TenantSectionKey };
export type TenantRelatedTable = RelatedTable & { key: TenantRelatedKey };

export type TenantDetailPage = {
    header: ResourceHeaderProps;
    availableTabs: TenantDetailTab[];
    workflow: ResourceWorkflow;
    stats: DetailItem[];
    sections: TenantDetailSection[];
    related: TenantRelatedTable[];
    documents: ResourceDocument[];
    timeline: ResourceTimelineEntry[];
};

export type TenantDetailPageProps = SharedProps & {
    detailPage: TenantDetailPage;
};
