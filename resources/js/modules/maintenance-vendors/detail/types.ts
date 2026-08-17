import type {
    DetailItem,
    DetailSection,
    ResourceAction,
    ResourceHeaderProps,
    ResourceTimelineEntry,
    ResourceWorkflow,
} from '@/components/resource-cycle';
import type { SharedProps } from '@/types';

export type VendorDetailTab =
    'overview' | 'workload' | 'schedule' | 'financial' | 'history';

export type VendorSectionKey =
    'identity' | 'contact' | 'schedule' | 'financial';

export type VendorDetailSection = DetailSection & { key: VendorSectionKey };

export type VendorWorkOrder = {
    id: number;
    reference: string;
    href: string;
    status: string;
    statusTone: 'primary' | 'teal' | 'danger' | 'muted';
    request: string;
    property: string;
    propertyCode?: string | null;
    tenant: string;
    assignedTo: string;
    scheduledAt?: string | null;
    schedule: string;
    scheduleTone: 'primary' | 'teal' | 'danger' | 'muted';
    estimated?: string | null;
    final?: string | null;
    scope?: string | null;
};

export type VendorNotice = {
    tone: 'primary' | 'teal' | 'danger' | 'muted';
    icon: string;
    title: string;
    description: string;
    actions: ResourceAction[];
};

export type VendorDetailPage = {
    header: ResourceHeaderProps;
    availableTabs: VendorDetailTab[];
    workflow: ResourceWorkflow;
    stats: DetailItem[];
    sections: VendorDetailSection[];
    workload: {
        open: VendorWorkOrder[];
        history: VendorWorkOrder[];
        allHref: string;
    };
    notices: Record<'workload' | 'schedule' | 'financial', VendorNotice>;
    timeline: ResourceTimelineEntry[];
};

export type VendorDetailPageProps = SharedProps & {
    detailPage: VendorDetailPage;
};
