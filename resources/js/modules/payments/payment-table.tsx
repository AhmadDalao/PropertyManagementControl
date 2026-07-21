import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import {
    RecordActions,
    StatusBadge,
    humanLabel,
} from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import { paymentFilterFields } from './payment-filters';
import type { PaymentIndexPageProps } from './types';

type PaymentTableProps = Pick<
    PaymentIndexPageProps,
    | 'payments'
    | 'filters'
    | 'counts'
    | 'portfolioOptions'
    | 'statusOptions'
    | 'typeOptions'
    | 'methodOptions'
    | 'auth'
    | 'app'
>;

export function PaymentTable(props: PaymentTableProps) {
    const { locale, t, text } = useTranslator();
    const filterFields = paymentFilterFields({
        statuses: props.statusOptions,
        types: props.typeOptions,
        methods: props.methodOptions,
        portfolios: props.portfolioOptions,
        includePortfolio:
            props.auth.user?.roles.includes('superadmin') ?? false,
    });

    return (
        <DataTable
            title="Payment ledger"
            description="Search reference, tenant, lease code, asset, or notes."
            data={props.payments}
            filters={props.filters}
            counts={props.counts}
            basePath="/payments"
            rowHref={(payment) => `/payments/${payment.id}`}
            exportHref={exportUrl('/exports/payments', props.filters)}
            filterFields={filterFields}
            emptyText="No payments yet. Create a lease, then post money here."
            columns={[
                {
                    key: 'reference',
                    label: 'Payment',
                    render: (payment) => (
                        <div className="pmc-primary-cell">
                            <strong>
                                {payment.reference ??
                                    t('payments.payment_number', undefined, {
                                        id: payment.id,
                                    })}
                            </strong>
                            <span>
                                {text(humanLabel(payment.method))} ·{' '}
                                {text(humanLabel(payment.type))}
                            </span>
                            <StatusBadge value={payment.status} />
                        </div>
                    ),
                },
                {
                    key: 'tenant',
                    label: 'Tenant / lease',
                    render: (payment) => (
                        <div className="pmc-stacked-cell">
                            <strong>
                                {payment.tenant_profile?.user?.name ??
                                    text('No tenant')}
                            </strong>
                            <span>
                                {payment.lease?.code ?? text('No lease')} ·{' '}
                                {(locale === 'ar'
                                    ? payment.lease?.leaseable?.title_ar ||
                                      payment.lease?.leaseable?.title_en
                                    : payment.lease?.leaseable?.title_en ||
                                      payment.lease?.leaseable?.title_ar) ??
                                    text('No asset')}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'date',
                    label: 'Received',
                    render: (payment) => (
                        <div className="pmc-stacked-cell">
                            <strong>
                                {humanDate(
                                    payment.received_on,
                                    props.app.locale,
                                )}
                            </strong>
                            <span>{text(humanLabel(payment.method))}</span>
                        </div>
                    ),
                },
                {
                    key: 'amount',
                    label: 'Amount',
                    render: (payment) => (
                        <div className="pmc-stacked-cell">
                            <strong>
                                {currency(
                                    payment.amount,
                                    props.app.locale,
                                    payment.currency,
                                )}
                            </strong>
                            <span>
                                {currency(
                                    payment.allocated_amount,
                                    props.app.locale,
                                    payment.currency,
                                )}{' '}
                                {text('allocated')}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'allocation',
                    label: 'Allocation',
                    render: (payment) => (
                        <div className="pmc-stacked-cell">
                            <strong>
                                {t('payments.installments', undefined, {
                                    count: payment.allocation_count,
                                })}
                            </strong>
                            <span>
                                {payment.unallocated_amount > 0
                                    ? t(
                                          'payments.unallocated_amount',
                                          undefined,
                                          {
                                              amount: currency(
                                                  payment.unallocated_amount,
                                                  locale,
                                                  payment.currency,
                                              ),
                                          },
                                      )
                                    : text('Fully allocated')}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'actions',
                    label: 'Actions',
                    className: 'text-end',
                    render: (payment) => (
                        <RecordActions
                            showHref={`/payments/${payment.id}`}
                            editHref={`/payments/${payment.id}/edit`}
                        >
                            {payment.status === 'posted' ? (
                                <a
                                    href={payment.receipt_url}
                                    className="btn btn-outline-secondary btn-sm"
                                >
                                    <i className="bi bi-receipt" />
                                    <span>{text('Receipt')}</span>
                                </a>
                            ) : null}
                            {payment.status !== 'void' ? (
                                <ArchiveAction
                                    href={`/payments/${payment.id}`}
                                    label="Void"
                                    confirmMessage={t(
                                        'payments.void_confirm',
                                        undefined,
                                        {
                                            reference:
                                                payment.reference ??
                                                `#${payment.id}`,
                                        },
                                    )}
                                />
                            ) : null}
                        </RecordActions>
                    ),
                },
            ]}
        />
    );
}
