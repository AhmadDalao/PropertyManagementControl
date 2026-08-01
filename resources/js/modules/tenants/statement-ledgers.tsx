import { useTranslator } from '@/lib/i18n';
import { currency, humanDate, localizedNumber } from '@/lib/utils';

import { StatementPanel, StatementRecord } from './statement-panel';
import type { TenantStatementPageProps } from './statement-types';

export function TenantInstallmentCards({
    props,
}: {
    props: TenantStatementPageProps;
}) {
    const { locale, t } = useTranslator();

    return (
        <StatementPanel
            title={t('tenants.installment_schedule')}
            description={t('tenants.installment_schedule_help')}
            count={props.counts.installments}
            empty={t('tenants.no_period_installments')}
            limit={props.limits.rows}
        >
            {props.installments.map((installment) => (
                <StatementRecord
                    href={installment.href}
                    key={installment.id}
                    title={
                        installment.lease_code ||
                        t('payments.payment_number', undefined, {
                            id: installment.id,
                        })
                    }
                    subtitle={`${humanDate(installment.due_date, locale)} · ${installment.label || t('tenants.installment')}`}
                    status={installment.status}
                    value={currency(
                        installment.remaining,
                        locale,
                        installment.currency,
                    )}
                    detail={t('tenants.installment_position', undefined, {
                        paid: currency(
                            installment.amount_paid,
                            locale,
                            installment.currency,
                        ),
                        due: currency(
                            installment.amount_due,
                            locale,
                            installment.currency,
                        ),
                    })}
                />
            ))}
        </StatementPanel>
    );
}

export function TenantPaymentCards({
    props,
}: {
    props: TenantStatementPageProps;
}) {
    const { locale, t, text } = useTranslator();

    return (
        <StatementPanel
            title={t('tenants.payment_ledger')}
            description={t('tenants.payment_ledger_help')}
            count={props.counts.payments}
            empty={t('tenants.no_period_payments')}
            limit={props.limits.rows}
        >
            {props.payments.map((payment) => (
                <StatementRecord
                    href={payment.href}
                    key={payment.id}
                    title={payment.reference}
                    subtitle={`${humanDate(payment.received_on, locale)} · ${payment.lease_code || t('tenants.no_lease')}`}
                    status={payment.status}
                    value={currency(payment.amount, locale, payment.currency)}
                    detail={text(payment.method.replaceAll('_', ' '))}
                />
            ))}
        </StatementPanel>
    );
}

export function TenantMaintenanceCards({
    props,
}: {
    props: TenantStatementPageProps;
}) {
    const { locale, t } = useTranslator();

    return (
        <StatementPanel
            title={t('tenants.maintenance_activity')}
            description={t('tenants.maintenance_activity_help')}
            count={props.counts.maintenance}
            empty={t('tenants.no_period_maintenance')}
            limit={props.limits.rows}
        >
            {props.maintenance.map((request) => (
                <StatementRecord
                    href={request.href}
                    key={request.id}
                    title={request.title}
                    subtitle={`${humanDate(request.requested_at, locale)} · ${
                        (locale === 'ar'
                            ? request.asset_ar || request.asset_en
                            : request.asset_en || request.asset_ar) ||
                        t('leases.no_asset')
                    }`}
                    status={request.status}
                    value={t(`status.${request.priority}`)}
                    detail={t('tenants.request_number', undefined, {
                        id: localizedNumber(request.id, locale),
                    })}
                />
            ))}
        </StatementPanel>
    );
}

export function TenantDocumentCards({
    props,
}: {
    props: TenantStatementPageProps;
}) {
    const { locale, t } = useTranslator();

    return (
        <StatementPanel
            title={t('tenants.account_documents')}
            description={t('tenants.account_documents_help')}
            count={props.counts.documents}
            empty={t('tenants.no_account_documents')}
            limit={props.limits.rows}
        >
            {props.documents.map((document) => (
                <a
                    className="pmc-tenant-statement-record"
                    href={document.download_url}
                    key={document.id}
                >
                    <div className="pmc-tenant-statement-record__head">
                        <div>
                            <strong>
                                {(locale === 'ar'
                                    ? document.title_ar || document.title_en
                                    : document.title_en || document.title_ar) ||
                                    t('documents.title')}
                            </strong>
                            <span>
                                {humanDate(document.created_at, locale)}
                            </span>
                        </div>
                        <i className="bi bi-download" aria-hidden="true" />
                    </div>
                    <div className="pmc-tenant-statement-record__meta">
                        <span>{t(`documents.options.${document.type}`)}</span>
                        <strong>PDF</strong>
                    </div>
                </a>
            ))}
        </StatementPanel>
    );
}
