import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { LeaseMetrics } from './lease-metrics';
import { LeaseTable } from './lease-table';
import type { LeaseIndexPageProps } from './types';

export default function LeasesIndexPage() {
    const { props } = usePage<LeaseIndexPageProps>();
    const { text } = useTranslator();

    return (
        <AdminLayout>
            <Head title={text('Leases')} />

            <WorkspaceHeader
                eyebrow="Portfolio"
                title="Leases"
                description="Find a contract and open it to manage installments, signed PDFs, balances, renewal, termination, and history."
                actions={[
                    {
                        label: 'Post payment',
                        href: '/payments/create',
                        icon: 'bi-cash-stack',
                    },
                    {
                        label: 'Create lease',
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
