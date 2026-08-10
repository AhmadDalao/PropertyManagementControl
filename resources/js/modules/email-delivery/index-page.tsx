import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { EmailDeliveryMetrics } from './email-delivery-metrics';
import { EmailDeliveryTable } from './email-delivery-table';
import type { EmailDeliveryIndexPageProps } from './types';

export default function EmailDeliveryIndexPage() {
    const { props } = usePage<EmailDeliveryIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('email_delivery.title')} />
            <WorkspaceHeader
                eyebrow={t('email_delivery.eyebrow')}
                title={t('email_delivery.title')}
                description={t('email_delivery.description')}
                actions={[
                    {
                        label: t('email_delivery.open_settings'),
                        href: '/system/settings',
                        icon: 'bi-sliders2',
                        tone: 'quiet',
                    },
                    {
                        label: t('email_delivery.open_readiness'),
                        href: '/system/readiness',
                        icon: 'bi-shield-check',
                        tone: 'quiet',
                    },
                ]}
            />
            <EmailDeliveryMetrics insights={props.insights} />
            <EmailDeliveryTable props={props} />
        </AdminLayout>
    );
}
