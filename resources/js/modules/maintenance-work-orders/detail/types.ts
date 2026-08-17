import type {
    DetailItem,
    DetailSection,
    ResourceAction,
    ResourceHeaderProps,
    ResourceTimelineEntry,
    ResourceWorkflow,
} from '@/components/resource-cycle';
import type { SharedProps } from '@/types';

export type WorkOrderDetailTab =
    'overview' | 'assignment' | 'schedule' | 'cost' | 'completion' | 'history';

export type WorkOrderSectionKey =
    'context' | 'scope' | 'assignment' | 'schedule' | 'cost' | 'completion';

export type WorkOrderDetailSection = DetailSection & {
    key: WorkOrderSectionKey;
};

export type WorkOrderNotice = {
    tone?: 'primary' | 'teal' | 'danger' | 'muted';
    icon: string;
    title: string;
    description: string;
    actions: ResourceAction[];
};

export type WorkOrderDetailPage = {
    header: ResourceHeaderProps;
    availableTabs: WorkOrderDetailTab[];
    workflow: ResourceWorkflow;
    stats: DetailItem[];
    sections: WorkOrderDetailSection[];
    notices: Record<
        'assignment' | 'schedule' | 'cost' | 'completion',
        WorkOrderNotice
    >;
    timeline: ResourceTimelineEntry[];
};

export type WorkOrderDetailPageProps = SharedProps & {
    detailPage: WorkOrderDetailPage;
};
