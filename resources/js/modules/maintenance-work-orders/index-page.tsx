import { Head, usePage } from '@inertiajs/react';

import { MetricGrid, WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import type { WorkOrderIndexProps } from './types';
import { WorkOrderTable } from './work-order-table';

export default function WorkOrderIndexPage() {
    const { props } = usePage<WorkOrderIndexProps>();
    const { t } = useTranslator();
    const insights = props.workOrderInsights;

    return (
        <AdminLayout>
            <Head title={t('work_orders.title')} />

            <WorkspaceHeader
                eyebrow={t('work_orders.workspace_eyebrow')}
                title={t('work_orders.title')}
                description={t('work_orders.workspace_description')}
                actions={[
                    {
                        label: t('work_orders.maintenance_queue'),
                        href: '/maintenance-requests',
                        icon: 'bi-tools',
                        tone: 'quiet',
                    },
                    {
                        label: t('work_orders.contractor_directory'),
                        href: '/maintenance-vendors',
                        icon: 'bi-building-check',
                        tone: 'secondary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: t('work_orders.active_jobs'),
                        value: insights.active,
                        detail: t('work_orders.active_jobs_help'),
                        icon: 'bi-activity',
                        tone: 'ink',
                        href: '/maintenance-work-orders?status=active',
                    },
                    {
                        label: t('work_orders.unscheduled_jobs'),
                        value: insights.unscheduled,
                        detail: t('work_orders.unscheduled_jobs_help'),
                        icon: 'bi-calendar2-x',
                        tone: insights.unscheduled > 0 ? 'amber' : 'teal',
                        href: '/maintenance-work-orders?schedule=unscheduled',
                    },
                    {
                        label: t('work_orders.overdue_visits'),
                        value: insights.overdue,
                        detail: t('work_orders.overdue_visits_help'),
                        icon: 'bi-exclamation-triangle',
                        tone: insights.overdue > 0 ? 'red' : 'teal',
                        href: '/maintenance-work-orders?schedule=overdue',
                    },
                    {
                        label: t('work_orders.completed_jobs'),
                        value: insights.completed,
                        detail: t('work_orders.completed_jobs_help'),
                        icon: 'bi-check2-circle',
                        tone: 'blue',
                        href: '/maintenance-work-orders?status=completed',
                    },
                ]}
            />

            <WorkOrderTable {...props} />
        </AdminLayout>
    );
}
