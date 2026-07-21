import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { MaintenanceMetrics } from './maintenance-metrics';
import { MaintenanceTable } from './maintenance-table';
import type { MaintenanceIndexPageProps } from './types';

export default function MaintenanceIndexPage() {
    const { props } = usePage<MaintenanceIndexPageProps>();
    const { text } = useTranslator();

    return (
        <AdminLayout>
            <Head title={text('Maintenance')} />

            <WorkspaceHeader
                eyebrow="Money & service"
                title="Maintenance"
                description={
                    props.mode === 'tenant'
                        ? 'Submit a property issue, then open the request to follow owner and manager updates.'
                        : 'Find a request and open it to assign work, update the tenant, record cost, resolve, or reopen.'
                }
                actions={[
                    ...(props.mode === 'manager'
                        ? [
                              {
                                  label: 'Expenses',
                                  href: '/expenses',
                                  icon: 'bi-receipt',
                                  tone: 'quiet' as const,
                              },
                          ]
                        : []),
                    {
                        label: 'Create request',
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
