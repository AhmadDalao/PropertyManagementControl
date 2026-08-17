import type {
    DetailItem,
    DetailSection,
    RelatedTable,
    ResourceDocument,
    ResourceHeaderProps,
    ResourceProgress,
    ResourceTimelineEntry,
    ResourceWorkflow,
} from '@/components/resource-cycle';
import type { SharedProps } from '@/types';

export type PortfolioDetailTab =
    | 'overview'
    | 'properties'
    | 'people'
    | 'operations'
    | 'financial'
    | 'documents'
    | 'history';

export type PortfolioSectionKey = 'profile' | 'ownership' | 'financial';
export type PortfolioRelatedKey =
    'properties' | 'people' | 'leases' | 'maintenance';

export type PortfolioDetailSection = DetailSection & {
    key: PortfolioSectionKey;
};
export type PortfolioRelatedTable = RelatedTable & {
    key: PortfolioRelatedKey;
};
export type PortfolioModule = {
    key: string;
    label: string;
    description: string;
    enabled: boolean;
};

export type PortfolioDetailPage = {
    header: ResourceHeaderProps;
    availableTabs: PortfolioDetailTab[];
    workflow: ResourceWorkflow;
    progress: ResourceProgress | null;
    stats: DetailItem[];
    sections: PortfolioDetailSection[];
    related: PortfolioRelatedTable[];
    modules: PortfolioModule[];
    documents: ResourceDocument[];
    timeline: ResourceTimelineEntry[];
};

export type PortfolioDetailPageProps = SharedProps & {
    detailPage: PortfolioDetailPage;
};
