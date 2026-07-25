import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { canCreateOperationalRecord } from '@/lib/access';
import { useTranslator } from '@/lib/i18n';

import { PaymentMetrics } from './payment-metrics';
import { PaymentTable } from './payment-table';
import type { PaymentIndexPageProps } from './types';

export default function PaymentsIndexPage() {
    const { props } = usePage<PaymentIndexPageProps>();
    const { t } = useTranslator();
    const canCreate = canCreateOperationalRecord(props.auth.user);

    return (
        <AdminLayout>
            <Head title={t('payments.title')} />

            <WorkspaceHeader
                eyebrow={t('payments.workspace_eyebrow')}
                title={t('payments.title')}
                description={t('payments.workspace_description')}
                actions={[
                    {
                        label: t('payments.reports'),
                        href: '/reports',
                        icon: 'bi-bar-chart-line',
                    },
                    ...(canCreate
                        ? [
                              {
                                  label: t('payments.record_payment'),
                                  href: '/payments/create',
                                  icon: 'bi-plus-lg',
                                  tone: 'primary' as const,
                              },
                          ]
                        : []),
                ]}
            />

            <PaymentMetrics {...props} />
            <PaymentTable {...props} />
        </AdminLayout>
    );
}
