import { Head, usePage } from '@inertiajs/react';

import { ResourceDetailShell } from '@/components/resource-cycle';
import type {
    DecisionCard,
    DetailItem,
    DetailSection,
    RelatedTable,
    ResourceDocument,
    ResourceHeaderProps,
    ResourceProgress,
    ResourceSpotlight,
    ResourceWorkflow,
} from '@/components/resource-cycle';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

type PageProps = SharedProps & {
    detailPage: {
        header: ResourceHeaderProps;
        spotlight?: ResourceSpotlight;
        workflow?: ResourceWorkflow;
        progress?: ResourceProgress | null;
        decisionCards?: DecisionCard[];
        stats?: DetailItem[];
        sections?: DetailSection[];
        related?: RelatedTable[];
        documents?: ResourceDocument[];
        timeline?: Array<{
            id: number;
            event: string;
            description?: string;
            causer?: string;
            created_at?: string;
        }>;
    };
};

export default function ResourceShowPage() {
    const { props } = usePage<PageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <ResourceDetailShell {...props.detailPage} />
        </AdminLayout>
    );
}
