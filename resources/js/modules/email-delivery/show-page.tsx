import { Head, usePage } from '@inertiajs/react';

import { ResourceDetailShell } from '@/components/resource-cycle';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import {
    deliveryStatusTone,
    formatDeliveryDate,
} from './email-delivery-format';
import type { EmailDeliveryShowPageProps } from './types';

export default function EmailDeliveryShowPage() {
    const { props } = usePage<EmailDeliveryShowPageProps>();
    const { locale, t } = useTranslator();
    const delivery = props.delivery;
    const statusTone = deliveryStatusTone(delivery.status);

    return (
        <AdminLayout>
            <Head
                title={t('email_delivery.attempt_number', undefined, {
                    id: delivery.id,
                })}
            />
            <ResourceDetailShell
                header={{
                    eyebrow: t('email_delivery.detail_eyebrow'),
                    title: t('email_delivery.attempt_number', undefined, {
                        id: delivery.id,
                    }),
                    description: t('email_delivery.detail_description'),
                    backHref: '/system/email-delivery',
                    backLabel: t('email_delivery.back_to_log'),
                }}
                workflow={{
                    eyebrow: t('email_delivery.transport_result'),
                    title: t(`email_delivery.status_titles.${delivery.status}`),
                    description: t(
                        `email_delivery.status_descriptions.${delivery.status}`,
                    ),
                    status: delivery.status_label,
                    tone:
                        statusTone === 'success'
                            ? 'teal'
                            : statusTone === 'danger'
                              ? 'danger'
                              : 'primary',
                    icon:
                        delivery.status === 'accepted'
                            ? 'bi-check-circle'
                            : delivery.status === 'failed'
                              ? 'bi-exclamation-triangle'
                              : 'bi-hourglass-split',
                }}
                stats={[
                    {
                        label: t('email_delivery.type'),
                        value: delivery.type_label,
                    },
                    {
                        label: t('email_delivery.attempts'),
                        value: delivery.attempts,
                    },
                    {
                        label: t('email_delivery.mailer'),
                        value: delivery.mailer,
                    },
                ]}
                sections={[
                    {
                        title: t('email_delivery.message_evidence'),
                        description: t('email_delivery.message_evidence_help'),
                        items: [
                            {
                                label: t('email_delivery.recipient'),
                                value: delivery.recipient_email,
                            },
                            {
                                label: t('email_delivery.subject'),
                                value: delivery.subject,
                            },
                            {
                                label: t('email_delivery.portfolio'),
                                value:
                                    delivery.portfolio ??
                                    t('email_delivery.system_scope'),
                            },
                            {
                                label: t('email_delivery.account'),
                                value:
                                    delivery.user ??
                                    t('email_delivery.no_account'),
                            },
                        ],
                    },
                    {
                        title: t('email_delivery.transport_evidence'),
                        description: t(
                            'email_delivery.transport_evidence_help',
                        ),
                        items: [
                            {
                                label: t('email_delivery.notification_id'),
                                value: delivery.notification_id,
                            },
                            {
                                label: t('email_delivery.transport_message_id'),
                                value: delivery.transport_message_id,
                            },
                            {
                                label: t('email_delivery.notification_class'),
                                value: delivery.notification_class
                                    ?.split('\\')
                                    .at(-1),
                            },
                            {
                                label: t('email_delivery.error'),
                                value:
                                    delivery.error_message ??
                                    t('email_delivery.no_error'),
                            },
                        ],
                    },
                    {
                        title: t('email_delivery.timing'),
                        items: [
                            {
                                label: t('email_delivery.started_at'),
                                value: formatDeliveryDate(
                                    delivery.started_at,
                                    locale,
                                ),
                            },
                            {
                                label: t('email_delivery.accepted_at'),
                                value: formatDeliveryDate(
                                    delivery.accepted_at,
                                    locale,
                                ),
                            },
                            {
                                label: t('email_delivery.failed_at'),
                                value: formatDeliveryDate(
                                    delivery.failed_at,
                                    locale,
                                ),
                            },
                        ],
                    },
                ]}
            />
        </AdminLayout>
    );
}
