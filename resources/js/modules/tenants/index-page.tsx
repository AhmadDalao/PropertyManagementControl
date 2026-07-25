import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { canCreateOperationalRecord } from '@/lib/access';
import { useTranslator } from '@/lib/i18n';

import { TenantMetrics } from './tenant-metrics';
import { TenantTable } from './tenant-table';
import type { TenantIndexPageProps } from './types';

export default function TenantsIndexPage() {
    const { props } = usePage<TenantIndexPageProps>();
    const { t } = useTranslator();
    const canCreate = canCreateOperationalRecord(props.auth.user);

    return (
        <AdminLayout>
            <Head title={t('tenants.title')} />

            <WorkspaceHeader
                eyebrow={t('tenants.workspace_eyebrow')}
                title={t('tenants.title')}
                description={t('tenants.workspace_description')}
                actions={
                    canCreate
                        ? [
                              {
                                  label: t('tenants.create_lease'),
                                  href: '/leases/create',
                                  icon: 'bi-file-earmark-plus',
                              },
                              {
                                  label: t('tenants.create_tenant'),
                                  href: '/tenants/create',
                                  icon: 'bi-person-plus',
                                  tone: 'primary',
                              },
                          ]
                        : []
                }
            />

            <TenantMetrics insights={props.tenantInsights} />
            <TenantTable {...props} />
        </AdminLayout>
    );
}
