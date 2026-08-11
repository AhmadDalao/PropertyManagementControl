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
                    ...(canCreate
                        ? [
                              {
                                  label: t('assets.add_property'),
                                  href: canSetupBuilding
                                      ? '/assets/building-setup'
                                      : '/assets/create',
                                  icon: 'bi-plus-lg',
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
