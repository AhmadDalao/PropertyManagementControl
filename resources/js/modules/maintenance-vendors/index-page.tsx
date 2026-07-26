import { Head, usePage } from '@inertiajs/react';

import { MetricGrid, WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import type { MaintenanceVendorIndexProps } from './types';
import { MaintenanceVendorTable } from './vendor-table';

export default function MaintenanceVendorIndexPage() {
    const { props } = usePage<MaintenanceVendorIndexProps>();
    const { t } = useTranslator();
    const insights = props.vendorInsights;

    return (
        <AdminLayout>
            <Head title={t('maintenance_vendors.title')} />

            <WorkspaceHeader
                eyebrow={t('maintenance_vendors.workspace_eyebrow')}
                title={t('maintenance_vendors.title')}
                description={t('maintenance_vendors.workspace_description')}
                actions={[
                    {
                        label: t('maintenance_vendors.maintenance_queue'),
                        href: '/maintenance-requests',
                        icon: 'bi-tools',
                        tone: 'quiet',
                    },
                    {
                        label: t('maintenance_vendors.create'),
                        href: '/maintenance-vendors/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: t('maintenance_vendors.active_vendors'),
                        value: insights.active,
                        detail: t(
                            'maintenance_vendors.vendor_total',
                            undefined,
                            { count: insights.total },
                        ),
                        icon: 'bi-building-check',
                        tone: 'ink',
                    },
                    {
                        label: t('maintenance_vendors.active_work_orders'),
                        value: insights.active_work_orders,
                        detail: t(
                            'maintenance_vendors.active_work_orders_help',
                        ),
                        icon: 'bi-clipboard2-check',
                        tone:
                            insights.active_work_orders > 0 ? 'amber' : 'teal',
                    },
                    {
                        label: t('maintenance_vendors.inactive_vendors'),
                        value: insights.inactive,
                        detail: t('maintenance_vendors.inactive_vendors_help'),
                        icon: 'bi-archive',
                        tone: 'blue',
                    },
                ]}
            />

            <MaintenanceVendorTable {...props} />
        </AdminLayout>
    );
}
