import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { LeaseRenewalMetrics } from './lease-renewal-metrics';
import { LeaseRenewalTable } from './lease-renewal-table';
import type { LeaseRenewalPageProps } from './types';

export default function LeaseRenewalIndexPage() {
    const { props } = usePage<LeaseRenewalPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('lease_renewals.title')} />

            <WorkspaceHeader
                eyebrow={t('lease_renewals.workspace_eyebrow')}
                title={t('lease_renewals.title')}
                description={t('lease_renewals.workspace_description')}
                actions={[
                    {
                        label: t('lease_renewals.all_leases'),
                        href: '/leases',
                        icon: 'bi-file-earmark-text',
                        tone: 'quiet',
                    },
                    {
                        label: t('lease_renewals.create_lease'),
                        href: '/leases/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <LeaseRenewalMetrics {...props} />
            <LeaseRenewalTable {...props} />
        </AdminLayout>
    );
}
