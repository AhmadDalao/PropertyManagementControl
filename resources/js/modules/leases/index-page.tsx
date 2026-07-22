import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { LeaseMetrics } from './lease-metrics';
import { LeaseTable } from './lease-table';
import type { LeaseIndexPageProps } from './types';

export default function LeasesIndexPage() {
    const { props } = usePage<LeaseIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('leases.title')} />

            <WorkspaceHeader
                eyebrow={t('leases.workspace_eyebrow')}
                title={t('leases.title')}
                description={t('leases.workspace_description')}
                actions={[
                    {
                        label: t('leases.post_payment'),
                        href: '/payments/create',
                        icon: 'bi-cash-stack',
                    },
                    {
                        label: t('leases.create_lease'),
                        href: '/leases/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <LeaseMetrics {...props} />
            <LeaseTable {...props} />
        </AdminLayout>
    );
}
