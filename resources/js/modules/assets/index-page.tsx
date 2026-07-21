import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { AssetMetrics } from './asset-metrics';
import { AssetTable } from './asset-table';
import type { AssetIndexPageProps } from './types';

export default function AssetsIndexPage() {
    const { props } = usePage<AssetIndexPageProps>();
    const { text } = useTranslator();

    return (
        <AdminLayout>
            <Head title={text('Properties & Units')} />

            <WorkspaceHeader
                eyebrow="Portfolio"
                title="Properties & units"
                description="Find a building, floor, unit, or space. Open the record for ownership, leases, documents, maintenance, and history."
                actions={[
                    {
                        label: 'Property map',
                        href: '/property-map',
                        icon: 'bi-map',
                    },
                    {
                        label: 'Create asset',
                        href: '/assets/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <AssetMetrics insights={props.insights} locale={props.app.locale} />
            <AssetTable {...props} />
        </AdminLayout>
    );
}
