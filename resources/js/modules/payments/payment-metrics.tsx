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
    const currencyCode = paymentInsights.currency ?? 'SAR';
    const money = (amount: number) =>
        paymentInsights.mixed_currencies
            ? t('payments.mixed_currencies')
            : compactCurrency(amount, app.locale, currencyCode);

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('payments.posted_payments'),
                    value: money(paymentInsights.posted_amount),
                    detail: t('payments.records', undefined, {
                        count: paymentInsights.posted_count,
                    }),
                    icon: 'bi-cash-stack',
                    tone: 'ink',
                },
                {
                    label: t('payments.this_month'),
                    value: money(paymentInsights.received_this_month),
                    detail: t('payments.posted_collections'),
                    icon: 'bi-calendar-check',
                    tone: 'teal',
                },
                {
                    label: t('payments.pending'),
                    value: money(paymentInsights.pending_amount),
                    detail: t('payments.waiting', undefined, {
                        count: paymentInsights.pending_count,
                    }),
                    icon: 'bi-hourglass-split',
                    tone: paymentInsights.pending_count > 0 ? 'amber' : 'blue',
                },
                {
                    label: t('payments.unallocated'),
                    value: money(paymentInsights.unallocated_amount),
                    detail: t('payments.allocated_amount', undefined, {
                        amount: paymentInsights.mixed_currencies
                            ? t('payments.mixed_currencies')
                            : currency(
                                  paymentInsights.allocated_amount,
                                  locale,
                                  currencyCode,
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
