import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { MaintenanceMetrics } from './maintenance-metrics';
import { MaintenanceTable } from './maintenance-table';
import type { MaintenanceIndexPageProps } from './types';

export default function MaintenanceIndexPage() {
    const { props } = usePage<MaintenanceIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('maintenance.title')} />

            <WorkspaceHeader
                eyebrow={t('maintenance.workspace_eyebrow')}
                title={t('maintenance.title')}
                description={
                    props.mode === 'tenant'
                        ? t('maintenance.tenant_description')
                        : t('maintenance.manager_description')
                }
                actions={[
                    ...(props.mode === 'manager'
                        ? [
                              {
                                  label: t('maintenance.expenses_action'),
                                  href: '/expenses',
                                  icon: 'bi-receipt',
                                  tone: 'quiet' as const,
                              },
                          ]
                        : []),
                    {
                        label: t('maintenance.create_request'),
                        href: '/maintenance-requests/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <MaintenanceMetrics {...props} />
            <MaintenanceTable {...props} />
        </AdminLayout>
    );
}
