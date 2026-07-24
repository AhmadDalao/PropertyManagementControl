import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { LeaseRenewalPageProps } from './types';

type LeaseRenewalMetricsProps = Pick<
    LeaseRenewalPageProps,
    'renewalInsights' | 'app'
>;

export function LeaseRenewalMetrics({
    renewalInsights,
    app,
}: LeaseRenewalMetricsProps) {
    const { t } = useTranslator();
    const count = (value: number) => localizedNumber(value, app.locale);

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('lease_renewals.action_required'),
                    value: count(renewalInsights.action_required),
                    detail: t('lease_renewals.action_required_help'),
                    icon: 'bi-exclamation-circle',
                    tone: renewalInsights.action_required > 0 ? 'red' : 'teal',
                    href: '/lease-renewals?queue=attention',
                },
                {
                    label: t('lease_renewals.ending_30_days'),
                    value: count(renewalInsights.ending_30_days),
                    detail: t('lease_renewals.ending_30_days_help'),
                    icon: 'bi-calendar2-check',
                    tone: renewalInsights.ending_30_days > 0 ? 'amber' : 'teal',
                    href: '/lease-renewals?queue=all&horizon=30&lease_status=active',
                },
                {
                    label: t('lease_renewals.renewals_prepared'),
                    value: count(renewalInsights.renewals_prepared),
                    detail: t('lease_renewals.renewals_prepared_help'),
                    icon: 'bi-file-earmark-text',
                    tone: 'blue',
                    href: '/lease-renewals?queue=prepared',
                },
                {
                    label: t('lease_renewals.expired_unresolved'),
                    value: count(renewalInsights.expired_unresolved),
                    detail: t('lease_renewals.expired_unresolved_help'),
                    icon: 'bi-hourglass-split',
                    tone:
                        renewalInsights.expired_unresolved > 0 ? 'red' : 'teal',
                    href: '/lease-renewals?queue=expired',
                },
            ]}
        />
    );
}
