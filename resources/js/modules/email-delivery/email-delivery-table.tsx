import { Link } from '@inertiajs/react';

import { DataTable, exportUrl } from '@/components/data-table';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { emailDeliveryFilterFields } from './email-delivery-filters';
import {
    deliveryStatusTone,
    formatDeliveryDate,
} from './email-delivery-format';
import type { EmailDeliveryIndexPageProps, EmailDeliveryRecord } from './types';

export function EmailDeliveryTable({
    props,
}: {
    props: EmailDeliveryIndexPageProps;
}) {
    const { locale, t } = useTranslator();

    return (
        <DataTable
            title={t('email_delivery.register_title')}
            description={t('email_delivery.register_description')}
            data={props.deliveries}
            filters={props.filters}
            counts={props.counts}
            basePath="/system/email-delivery"
            exportHref={exportUrl(
                '/system/email-delivery/export',
                props.filters,
            )}
            filterFields={emailDeliveryFilterFields(props, t)}
            emptyText={t('email_delivery.no_matches')}
            rowHref={(delivery) => delivery.url}
            mobileCard={{
                title: (delivery) => delivery.recipient_email,
                subtitle: (delivery) => delivery.subject,
                status: (delivery) => <DeliveryStatus delivery={delivery} />,
                meta: [
                    {
                        label: t('email_delivery.type'),
                        value: (delivery) => delivery.type_label,
                    },
                    {
                        label: t('email_delivery.attempts'),
                        value: (delivery) => delivery.attempts,
                    },
                    {
                        label: t('email_delivery.started_at'),
                        value: (delivery) =>
                            formatDeliveryDate(delivery.started_at, locale),
                    },
                ],
                actions: (delivery) => <OpenDelivery delivery={delivery} />,
            }}
            columns={[
                {
                    key: 'result',
                    label: t('email_delivery.result'),
                    render: (delivery) => (
                        <div className="pmc-stacked-cell">
                            <DeliveryStatus delivery={delivery} />
                            <span>
                                {formatDeliveryDate(
                                    delivery.started_at,
                                    locale,
                                )}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'message',
                    label: t('email_delivery.message'),
                    render: (delivery) => (
                        <div className="pmc-stacked-cell">
                            <strong>{delivery.recipient_email}</strong>
                            <span>{delivery.subject}</span>
                            <small>{delivery.type_label}</small>
                        </div>
                    ),
                },
                {
                    key: 'scope',
                    label: t('email_delivery.scope'),
                    render: (delivery) => (
                        <div className="pmc-stacked-cell">
                            <strong>
                                {delivery.portfolio ??
                                    t('email_delivery.system_scope')}
                            </strong>
                            <span>
                                {delivery.user ??
                                    t('email_delivery.no_account')}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'transport',
                    label: t('email_delivery.transport'),
                    render: (delivery) => (
                        <div className="pmc-stacked-cell">
                            <strong>{delivery.mailer}</strong>
                            <span>
                                {t('email_delivery.attempt_count', undefined, {
                                    count: delivery.attempts,
                                })}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'actions',
                    label: t('email_delivery.actions'),
                    className: 'text-end',
                    render: (delivery) => <OpenDelivery delivery={delivery} />,
                },
            ]}
        />
    );
}

function DeliveryStatus({ delivery }: { delivery: EmailDeliveryRecord }) {
    return (
        <StatusBadge
            value={delivery.status}
            label={delivery.status_label}
            tone={deliveryStatusTone(delivery.status)}
        />
    );
}

function OpenDelivery({ delivery }: { delivery: EmailDeliveryRecord }) {
    const { t } = useTranslator();

    return (
        <Link href={delivery.url} className="pmc-record-open">
            {t('email_delivery.open_attempt')}
            <i className="bi bi-arrow-up-right" />
        </Link>
    );
}
