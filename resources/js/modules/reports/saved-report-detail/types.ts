import type {
    DetailItem,
    DetailSection,
    ResourceHeaderProps,
    ResourceTimelineEntry,
    ResourceWorkflow,
} from '@/components/resource-cycle';
import type { SharedProps } from '@/types';

export type SavedReportDetailTab =
    'overview' | 'scope' | 'outputs' | 'access' | 'history';

export type SavedReportSection = DetailSection & {
    key: 'identity' | 'scope' | 'access';
};

export type SavedReportOutput = {
    id: number;
    title: string;
    subtitle: string;
    format: 'PDF' | 'DOCX' | 'XLSX';
    description: string;
    label: string;
    icon: string;
    href: string;
};

export type SavedReportNotice = {
    tone: 'primary' | 'teal' | 'danger' | 'muted';
    icon: string;
    title: string;
    description: string;
};

export type SavedReportDetailPage = {
    header: ResourceHeaderProps;
    availableTabs: SavedReportDetailTab[];
    workflow: ResourceWorkflow;
    stats: DetailItem[];
    sections: SavedReportSection[];
    outputs: SavedReportOutput[];
    notices: Record<'scope' | 'outputs' | 'access', SavedReportNotice>;
    timeline: ResourceTimelineEntry[];
};

export type SavedReportDetailPageProps = SharedProps & {
    detailPage: SavedReportDetailPage;
};
