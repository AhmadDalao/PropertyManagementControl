import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency, currency } from '@/lib/utils';

import type { PaymentIndexPageProps } from './types';

type PaymentMetricsProps = Pick<
    PaymentIndexPageProps,
    'paymentInsights' | 'app'
>;

export function PaymentMetrics({ paymentInsights, app }: PaymentMetricsProps) {
    const { locale, t } = useTranslator();

    return (
        <MetricGrid
            metrics={[
                {
                    label: 'Posted payments',
                    value: compactCurrency(
                        paymentInsights.posted_amount,
                        app.locale,
                    ),
                    detail: t('payments.records', undefined, {
                        count: paymentInsights.posted_count,
                    }),
                    icon: 'bi-cash-stack',
                    tone: 'ink',
                },
                {
                    label: 'This month',
                    value: compactCurrency(
                        paymentInsights.received_this_month,
                        app.locale,
                    ),
                    detail: 'Posted collections',
                    icon: 'bi-calendar-check',
                    tone: 'teal',
                },
                {
                    label: 'Pending',
                    value: compactCurrency(
                        paymentInsights.pending_amount,
                        app.locale,
                    ),
                    detail: t('payments.waiting', undefined, {
                        count: paymentInsights.pending_count,
                    }),
                    icon: 'bi-hourglass-split',
                    tone: paymentInsights.pending_count > 0 ? 'amber' : 'blue',
                },
                {
                    label: 'Unallocated',
                    value: compactCurrency(
                        paymentInsights.unallocated_amount,
                        app.locale,
                    ),
                    detail: t('payments.allocated_amount', undefined, {
                        amount: currency(
                            paymentInsights.allocated_amount,
                            locale,
                        ),
                    }),
                    icon: 'bi-diagram-3',
                    tone:
                        paymentInsights.unallocated_amount > 0 ? 'red' : 'blue',
                },
            ]}
        />
    );
}
