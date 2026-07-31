import { humanLabel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import { ReportRecordSection } from './report-visuals';
import type { OwnerStatementPageProps } from './types';

export function OwnerStatementRecords({
    props,
}: {
    props: OwnerStatementPageProps;
}) {
    const { locale, t, text } = useTranslator();

    return (
        <section className="pmc-statement-record-grid">
            <ReportRecordSection
                title={t('reports.contracts_in_arrears')}
                description={t('reports.statement_arrears_help')}
                empty={t('reports.no_arrears')}
                rows={props.arrearsLeases.map((lease) => ({
                    href: `/leases/${lease.id}`,
                    title: lease.code,
                    meta: `${lease.tenant ?? t('reports.no_tenant')} · ${lease.asset ?? t('reports.no_asset')}`,
                    value: currency(
                        lease.arrears_amount,
                        locale,
                        lease.currency,
                    ),
                    tone: 'danger',
                }))}
            />
            <ReportRecordSection
                title={t('reports.recent_payments')}
                description={t('reports.statement_payments_help')}
                empty={t('reports.no_recent_payments')}
                rows={props.recentPayments.map((payment) => ({
                    href: `/payments/${payment.id}`,
                    title: payment.reference,
                    meta: `${payment.tenant ?? t('reports.no_tenant')} · ${humanDate(payment.received_on, locale)}`,
                    value: currency(payment.amount, locale, payment.currency),
                    tone: 'success',
                }))}
            />
            <ReportRecordSection
                title={t('reports.maintenance_backlog')}
                description={t('reports.statement_maintenance_help')}
                empty={t('reports.no_maintenance_backlog')}
                rows={props.maintenanceBacklog.map((request) => ({
                    href: `/maintenance-requests/${request.id}`,
                    title: request.title,
                    meta: `${request.asset ?? t('reports.no_asset')} · ${text(humanLabel(request.priority))}`,
                    value: text(humanLabel(request.status)),
                    status: request.status,
                }))}
            />
        </section>
    );
}
