import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/tenant-portal.css';

import { MetricGrid, WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { currency, localizedNumber } from '@/lib/utils';

import { PaymentRecords } from './payment-records';
import { PortalEmpty } from './portal-empty';
import { PortalFilters } from './portal-filters';
import type { TenantPaymentsPageProps } from './types';

export default function TenantPaymentsPage() {
    const { props } = usePage<TenantPaymentsPageProps>();
    const { locale, t } = useTranslator();
    const primary = props.financials[0];

    return (
        <AdminLayout>
            <Head title={t('tenant_portal.my_payments')} />
            <WorkspaceHeader
                eyebrow={t('tenant_portal.portal_eyebrow')}
                title={t('tenant_portal.my_payments')}
                description={t('tenant_portal.payments_description')}
                actions={[
                    {
                        label: t('tenant_portal.my_lease'),
                        href: '/my-lease',
                        icon: 'bi-file-earmark-text',
                        tone: 'quiet',
                    },
                ]}
            />
            <MetricGrid
                metrics={[
                    {
                        label: t('tenant_portal.total_paid'),
                        value: primary
                            ? currency(primary.paid, locale, primary.currency)
                            : currency(0, locale),
                        detail: t('tenant_portal.all_contracts'),
                        icon: 'bi-check2-circle',
                        tone: 'teal',
                    },
                    {
                        label: t('tenant_portal.outstanding'),
                        value: primary
                            ? currency(
                                  primary.outstanding,
                                  locale,
                                  primary.currency,
                              )
                            : currency(0, locale),
                        detail: t('tenant_portal.manual_payment_note'),
                        icon: 'bi-wallet2',
                        tone: primary?.overdue ? 'red' : 'amber',
                    },
                    {
                        label: t('tenant_portal.recorded_payments'),
                        value: localizedNumber(props.counts.all ?? 0, locale),
                        detail: t('tenant_portal.posted_count', undefined, {
                            count: props.counts.posted ?? 0,
                        }),
                        icon: 'bi-receipt',
                        tone: 'ink',
                    },
                    {
                        label: t('tenant_portal.pending_review'),
                        value: localizedNumber(
                            props.counts.pending ?? 0,
                            locale,
                        ),
                        detail: t('tenant_portal.pending_description'),
                        icon: 'bi-hourglass-split',
                        tone: 'blue',
                    },
                ]}
            />
            <PortalFilters
                basePath="/my-payments"
                filters={props.filters}
                leases={props.leases}
                mode="payments"
            />
            {props.payments.total === 0 &&
            !props.filters.search &&
            props.filters.status === 'all' &&
            !props.filters.lease_id ? (
                <PortalEmpty kind="payment" />
            ) : (
                <PaymentRecords props={props} />
            )}
        </AdminLayout>
    );
}
