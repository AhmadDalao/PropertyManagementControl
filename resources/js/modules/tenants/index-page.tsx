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
                                  label: t('tenants.start_tenancy'),
                                  href: '/tenants/create?next=lease',
                                  icon: 'bi-file-earmark-plus',
                                  tone: 'primary',
                              },
                              {
                                  label: t('tenants.create_tenant'),
                                  href: '/tenants/create',
                                  icon: 'bi-person-plus',
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
