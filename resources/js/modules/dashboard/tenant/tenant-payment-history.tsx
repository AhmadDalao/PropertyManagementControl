import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { TenantDashboardProps } from '../types';

export function TenantPaymentHistory({
    props,
}: {
    props: TenantDashboardProps;
}) {
    const { t } = useTranslator();
    const payments = props.tenantPortal.payments;

    return (
        <WorkspacePanel
            eyebrow={t('dashboard.tenant_payments_eyebrow')}
            title={t('dashboard.tenant_payment_history')}
            description={t('dashboard.tenant_payment_history_description')}
            action={{ label: t('actions.view_all'), href: '/my-payments' }}
        >
            <div className="pmc-command-list">
                {payments.length > 0 ? (
                    payments.slice(0, 7).map((payment) => (
                        <a key={payment.id} href={payment.receipt_url}>
                            <div>
                                <strong>
                                    {payment.reference ??
                                        t(
                                            'dashboard.receipt_number',
                                            undefined,
                                            { id: payment.id },
                                        )}
                                </strong>
                                <span>
                                    {humanDate(
                                        payment.received_on,
                                        props.app.locale,
                                    )}
                                </span>
                            </div>
                            <em className="is-success">
                                {currency(
                                    payment.amount,
                                    props.app.locale,
                                    payment.currency,
                                )}
                            </em>
                        </a>
                    ))
                ) : (
                    <div className="pmc-command-empty">
                        {t('tenant_portal.empty_payment_description')}
                    </div>
                )}
            </div>
        </WorkspacePanel>
    );
}
