import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { LeaseMoveOutMetrics } from './lease-move-out-metrics';
import { LeaseMoveOutTable } from './lease-move-out-table';
import type { LeaseMoveOutPageProps } from './types';

export default function LeaseMoveOutIndexPage() {
    const { props } = usePage<LeaseMoveOutPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('lease_move_outs.title')} />

            <WorkspaceHeader
                eyebrow={t('lease_move_outs.workspace_eyebrow')}
                title={t('lease_move_outs.title')}
                description={t('lease_move_outs.workspace_description')}
                actions={[
                    {
                        label: t('lease_move_outs.lease_renewals'),
                        href: '/lease-renewals',
                        icon: 'bi-calendar-event',
                        tone: 'quiet',
                    },
                    {
                        label: t('lease_move_outs.all_leases'),
                        href: '/leases',
                        icon: 'bi-file-earmark-text',
                    },
                ]}
            />

            <LeaseMoveOutMetrics {...props} />
            <LeaseMoveOutTable {...props} />
        </AdminLayout>
    );
}
