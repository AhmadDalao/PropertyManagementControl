import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { PaymentMetrics } from './payment-metrics';
import { PaymentTable } from './payment-table';
import type { PaymentIndexPageProps } from './types';

export default function PaymentsIndexPage() {
    const { props } = usePage<PaymentIndexPageProps>();
    const { text } = useTranslator();

    return (
        <AdminLayout>
            <Head title={text('Payments')} />

            <WorkspaceHeader
                eyebrow="Money & service"
                title="Payments"
                description="Review money received, verify installment allocation, download receipts, and void incorrect entries safely."
                actions={[
                    {
                        label: 'Reports',
                        href: '/reports',
                        icon: 'bi-bar-chart-line',
                    },
                    {
                        label: 'Post payment',
                        href: '/payments/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <PaymentMetrics {...props} />
            <PaymentTable {...props} />
        </AdminLayout>
    );
}
