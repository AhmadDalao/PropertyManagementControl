import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import { DashboardRecordList } from '../shared/record-list';
import type { OperationsDashboardProps } from '../types';
import { propertyFocusUrl } from './property-focus-url';

export function RecentPaymentsPanel({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { t } = useTranslator();
    const propertyId = props.propertyFocus.selected?.id;

    return (
        <WorkspacePanel
            eyebrow={t('dashboard.activity')}
            title={t('dashboard.recent_payments')}
            description={t('dashboard.recent_payments_description')}
            action={{
                label: t('actions.view_all'),
                href: propertyFocusUrl('/payments', propertyId),
            }}
        >
            <DashboardRecordList
                empty={t('dashboard.no_recent_payments')}
                rows={props.recentPayments.slice(0, 4).map((payment) => ({
                    href: `/payments/${payment.id}`,
                    title:
                        payment.tenant_profile?.user?.name ??
                        t('payments.payment_number', undefined, {
                            id: payment.id,
                        }),
                    meta: humanDate(payment.received_on, props.app.locale),
                    value: currency(
                        payment.amount,
                        props.app.locale,
                        payment.currency,
                    ),
                    tone: 'success',
                }))}
            />
        </WorkspacePanel>
    );
}
