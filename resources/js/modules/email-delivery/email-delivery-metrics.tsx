import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { EmailDeliveryInsights } from './types';

export function EmailDeliveryMetrics({
    insights,
}: {
    insights: EmailDeliveryInsights;
}) {
    const { locale, t } = useTranslator();
    const rate = new Intl.NumberFormat(locale === 'ar' ? 'ar-SA' : 'en', {
        style: 'percent',
        maximumFractionDigits: 1,
    }).format(insights.acceptance_rate / 100);

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('email_delivery.total_attempts'),
                    value: insights.total,
                    detail: t('email_delivery.total_attempts_help'),
                    icon: 'bi-envelope-paper',
                    tone: 'ink',
                },
                {
                    label: t('email_delivery.accepted'),
                    value: insights.accepted,
                    detail: t('email_delivery.accepted_help'),
                    icon: 'bi-check-circle',
                    tone: 'teal',
                },
                {
                    label: t('email_delivery.failed'),
                    value: insights.failed,
                    detail: t('email_delivery.failed_help'),
                    icon: 'bi-exclamation-triangle',
                    tone: 'red',
                },
                {
                    label: t('email_delivery.acceptance_rate'),
                    value: rate,
                    detail: t('email_delivery.acceptance_rate_help'),
                    icon: 'bi-activity',
                    tone: 'blue',
                },
            ]}
        />
    );
}
