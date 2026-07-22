import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { AssetMetrics } from './asset-metrics';
import { AssetTable } from './asset-table';
import type { AssetIndexPageProps } from './types';

export default function AssetsIndexPage() {
    const { props } = usePage<AssetIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('assets.title')} />

            <WorkspaceHeader
                eyebrow={t('assets.workspace_eyebrow')}
                title={t('assets.title')}
                description={t('assets.workspace_description')}
                actions={[
                    {
                        label: t('assets.property_map'),
                        href: '/property-map',
                        icon: 'bi-map',
                    },
                    {
                        label: t('assets.create_asset'),
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
