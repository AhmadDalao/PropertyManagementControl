import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { TenantDashboardProps } from '../types';

export function TenantPaymentHistory({
    props,
}: {
    props: TenantDashboardProps;
}) {
    const { t, text } = useTranslator();
    const payments = props.tenantPortal.payments;

    return (
        <WorkspacePanel
            eyebrow="Payments"
            title="Payment history"
            description="Posted rent payments and downloadable receipts."
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
                        {text('No posted payments yet.')}
                    </div>
                )}
            </div>
        </WorkspacePanel>
    );
}
