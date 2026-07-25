import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { canCreateOperationalRecord } from '@/lib/access';
import { useTranslator } from '@/lib/i18n';

import { AssetMetrics } from './asset-metrics';
import { AssetTable } from './asset-table';
import type { AssetIndexPageProps } from './types';

export default function AssetsIndexPage() {
    const { props } = usePage<AssetIndexPageProps>();
    const { t } = useTranslator();
    const canCreate = canCreateOperationalRecord(props.auth.user);
    const canSetupBuilding =
        props.auth.user?.roles.some((role) =>
            ['superadmin', 'owner'].includes(role),
        ) ?? false;

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
                    ...(canCreate
                        ? [
                              {
                                  label: t('assets.builder.single_asset'),
                                  href: '/assets/create',
                                  icon: 'bi-plus-lg',
                                  tone: canSetupBuilding
                                      ? ('secondary' as const)
                                      : ('primary' as const),
                              },
                          ]
                        : []),
                    ...(canSetupBuilding
                        ? [
                              {
                                  label: t('assets.builder.setup_building'),
                                  href: '/assets/building-setup',
                                  icon: 'bi-buildings',
                                  tone: 'primary' as const,
                              },
                          ]
                        : []),
                ]}
            />

            <AssetMetrics insights={props.insights} locale={props.app.locale} />
            <AssetTable {...props} />
        </AdminLayout>
    );
}
