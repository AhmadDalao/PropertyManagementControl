import { Link } from '@inertiajs/react';

import { TablePagination } from '@/components/data-table/table-pagination';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { TenantPaymentsPageProps } from './types';

export function PaymentRecords({ props }: { props: TenantPaymentsPageProps }) {
    const { locale, t } = useTranslator();

    return (
        <section className="pmc-portal-panel pmc-portal-register">
            <header>
                <div>
                    <span>{t('tenant_portal.payment_history')}</span>
                    <h2>{t('tenant_portal.payments_and_receipts')}</h2>
                </div>
                <small>{t('tenant_portal.manual_payment_note')}</small>
            </header>
            <div className="pmc-portal-table pmc-portal-table-desktop">
                <table>
                    <thead>
                        <tr>
                            <th>{t('tenant_portal.date')}</th>
                            <th>{t('tenant_portal.reference')}</th>
                            <th>{t('tenant_portal.lease')}</th>
                            <th>{t('tenant_portal.method')}</th>
                            <th>{t('tenant_portal.amount')}</th>
                            <th>{t('tenant_portal.status')}</th>
                            <th>
                                <span className="visually-hidden">
                                    {t('common.actions')}
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {props.payments.data.map((payment) => (
                            <tr key={payment.id}>
                                <td>
                                    {humanDate(payment.received_on, locale)}
                                </td>
                                <td>
                                    <Link href={`/payments/${payment.id}`}>
                                        <strong>
                                            {payment.reference ||
                                                `#${payment.id}`}
                                        </strong>
                                    </Link>
                                    <small>
                                        {t(`payments.${payment.type}`)}
                                    </small>
                                </td>
                                <td>{payment.lease?.code || '-'}</td>
                                <td>{t(`payments.${payment.method}`)}</td>
                                <td>
                                    {currency(
                                        payment.amount,
                                        locale,
                                        payment.currency,
                                    )}
                                </td>
                                <td>
                                    <StatusBadge value={payment.status} />
                                </td>
                                <td>
                                    {payment.status === 'posted' ? (
                                        <a
                                            className="pmc-portal-icon-action"
                                            href={payment.receipt_url}
                                            aria-label={t(
                                                'tenant_portal.download_receipt',
                                            )}
                                        >
                                            <i className="bi bi-download" />
                                        </a>
                                    ) : null}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="pmc-portal-records">
                {props.payments.data.map((payment) => (
                    <article key={payment.id}>
                        <header>
                            <Link href={`/payments/${payment.id}`}>
                                {payment.reference || `#${payment.id}`}
                            </Link>
                            <StatusBadge value={payment.status} />
                        </header>
                        <p>
                            {payment.lease?.code} ·{' '}
                            {t(`payments.${payment.method}`)}
                        </p>
                        <dl>
                            <div>
                                <dt>{t('tenant_portal.date')}</dt>
                                <dd>
                                    {humanDate(payment.received_on, locale)}
                                </dd>
                            </div>
                            <div>
                                <dt>{t('tenant_portal.amount')}</dt>
                                <dd>
                                    {currency(
                                        payment.amount,
                                        locale,
                                        payment.currency,
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt>{t('tenant_portal.type')}</dt>
                                <dd>{t(`payments.${payment.type}`)}</dd>
                            </div>
                        </dl>
                        {payment.status === 'posted' ? (
                            <a
                                className="pmc-portal-record-action"
                                href={payment.receipt_url}
                            >
                                <i className="bi bi-download" />
                                {t('tenant_portal.download_receipt')}
                            </a>
                        ) : null}
                    </article>
                ))}
            </div>
            <TablePagination data={props.payments} />
        </section>
    );
}
