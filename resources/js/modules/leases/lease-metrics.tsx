import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency, currency } from '@/lib/utils';

import type { LeaseIndexPageProps } from './types';

type LeaseMetricsProps = Pick<LeaseIndexPageProps, 'leaseInsights' | 'app'>;

export function LeaseMetrics({ leaseInsights, app }: LeaseMetricsProps) {
    const { locale, t } = useTranslator();
    const attention = leaseInsights.unsigned + leaseInsights.expiring_soon;

    return (
        <MetricGrid
            metrics={[
                {
                    label: 'Active leases',
                    value: leaseInsights.active,
                    detail: t('leases.total_contracts', undefined, {
                        count: leaseInsights.total,
                    }),
                    icon: 'bi-file-earmark-text',
                    tone: 'ink',
                },
                {
                    label: 'Collected',
                    value: compactCurrency(
                        leaseInsights.total_paid,
                        app.locale,
                    ),
                    detail: t('leases.scheduled_amount', undefined, {
                        amount: currency(leaseInsights.total_due, locale),
                    }),
                    icon: 'bi-check-circle',
                    tone: 'teal',
                },
                {
                    label: 'Outstanding',
                    value: compactCurrency(
                        leaseInsights.balance_remaining,
                        app.locale,
                    ),
                    detail: t('leases.overdue_contracts', undefined, {
                        count: leaseInsights.overdue,
                    }),
                    icon: 'bi-hourglass-split',
                    tone: leaseInsights.balance_remaining > 0 ? 'red' : 'blue',
                },
                {
                    label: 'Contract attention',
                    value: attention,
                    detail: t('leases.attention_mix', undefined, {
                        unsigned: leaseInsights.unsigned,
                        expiring: leaseInsights.expiring_soon,
                    }),
                    icon: 'bi-file-earmark-excel',
                    tone: attention > 0 ? 'amber' : 'blue',
                },
            ]}
        />
    );
}
