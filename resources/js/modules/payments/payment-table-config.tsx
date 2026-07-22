import { ArchiveAction } from '@/components/archive-action';
import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { PaymentRecord } from './types';

export function usePaymentTableConfig(locale: string): {
    columns: Array<TableColumn<PaymentRecord>>;
    mobileCard: MobileTableConfig<PaymentRecord>;
} {
    const { t } = useTranslator();
    const tenantLease = (payment: PaymentRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {payment.tenant_profile?.user?.name ?? t('payments.no_tenant')}
            </strong>
            <span>
                {payment.lease?.code ?? t('payments.no_lease')} ·{' '}
                {localizedAsset(payment, locale) ?? t('payments.no_asset')}
            </span>
        </div>
    );
    const actions = (payment: PaymentRecord) => (
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
                    <span>{t('payments.receipt')}</span>
                </a>
            ) : null}
            {payment.status !== 'void' ? (
                <ArchiveAction
                    href={`/payments/${payment.id}`}
                    label={t('payments.void')}
                    confirmMessage={t('payments.void_confirm', undefined, {
                        reference: payment.reference ?? `#${payment.id}`,
                    })}
                />
            ) : null}
        </RecordActions>
    );
    const columns: Array<TableColumn<PaymentRecord>> = [
        {
            key: 'reference',
            label: t('payments.payment'),
            render: (payment) => (
                <div className="pmc-primary-cell">
                    <strong>
                        {payment.reference ??
                            t('payments.payment_number', undefined, {
                                id: payment.id,
                            })}
                    </strong>
                    <span>
                        {methodLabel(payment.method, t)} ·{' '}
                        {typeLabel(payment.type, t)}
                    </span>
                    <StatusBadge
                        value={payment.status}
                        label={statusLabel(payment.status, t)}
                    />
                </div>
            ),
        },
        {
            key: 'tenant',
            label: t('payments.tenant_lease'),
            render: tenantLease,
        },
        {
            key: 'date',
            label: t('payments.received'),
            render: (payment) => (
                <div className="pmc-stacked-cell">
                    <strong>{humanDate(payment.received_on, locale)}</strong>
                    <span>{methodLabel(payment.method, t)}</span>
                </div>
            ),
        },
        {
            key: 'amount',
            label: t('payments.amount'),
            render: (payment) => (
                <div className="pmc-stacked-cell">
                    <strong>
                        {currency(payment.amount, locale, payment.currency)}
                    </strong>
                    <span>
                        {currency(
                            payment.allocated_amount,
                            locale,
                            payment.currency,
                        )}{' '}
                        {t('payments.allocated_label')}
                    </span>
                </div>
            ),
        },
        {
            key: 'allocation',
            label: t('payments.allocation'),
            render: (payment) => (
                <div className="pmc-stacked-cell">
                    <strong>
                        {t('payments.installments', undefined, {
                            count: payment.allocation_count,
                        })}
                    </strong>
                    <span>
                        {payment.unallocated_amount > 0
                            ? t('payments.unallocated_amount', undefined, {
                                  amount: currency(
                                      payment.unallocated_amount,
                                      locale,
                                      payment.currency,
                                  ),
                              })
                            : t('payments.fully_allocated')}
                    </span>
                </div>
            ),
        },
        {
            key: 'actions',
            label: t('payments.actions'),
            className: 'text-end',
            render: actions,
        },
    ];

    return {
        columns,
        mobileCard: {
            title: (payment) =>
                payment.reference ??
                t('payments.payment_number', undefined, { id: payment.id }),
            subtitle: tenantLease,
            status: (payment) => (
                <StatusBadge
                    value={payment.status}
                    label={statusLabel(payment.status, t)}
                />
            ),
            meta: [
                {
                    label: t('payments.received'),
                    value: (payment) => humanDate(payment.received_on, locale),
                },
                {
                    label: t('payments.amount'),
                    value: (payment) =>
                        currency(payment.amount, locale, payment.currency),
                },
                {
                    label: t('payments.allocation'),
                    value: (payment) =>
                        payment.unallocated_amount > 0
                            ? t('payments.unallocated_amount', undefined, {
                                  amount: currency(
                                      payment.unallocated_amount,
                                      locale,
                                      payment.currency,
                                  ),
                              })
                            : t('payments.fully_allocated'),
                },
            ],
            actions,
        },
    };
}

function localizedAsset(payment: PaymentRecord, locale: string) {
    return locale === 'ar'
        ? payment.lease?.leaseable?.title_ar ||
              payment.lease?.leaseable?.title_en
        : payment.lease?.leaseable?.title_en ||
              payment.lease?.leaseable?.title_ar;
}

function statusLabel(status: string, t: ReturnType<typeof useTranslator>['t']) {
    return t(`status.${status}` as UiTranslationKey);
}

function typeLabel(type: string, t: ReturnType<typeof useTranslator>['t']) {
    return t(`payments.type_${type}` as UiTranslationKey);
}

function methodLabel(method: string, t: ReturnType<typeof useTranslator>['t']) {
    return t(`payments.method_${method}` as UiTranslationKey);
}
