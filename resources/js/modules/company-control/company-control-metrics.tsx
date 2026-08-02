import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency, localizedNumber, percent } from '@/lib/utils';

import type { CompanyControlSummary } from './types';

export function CompanyControlMetrics({
    summary,
}: {
    summary: CompanyControlSummary;
}) {
    const { locale, t } = useTranslator();
    const valuations = summary.valuation_totals
        .map((position) =>
            compactCurrency(position.amount, locale, position.currency),
        )
        .join(' · ');
    const arrears = summary.currency_totals
        .map((position) =>
            compactCurrency(position.arrears, locale, position.currency),
        )
        .join(' · ');

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('company_control.portfolios_in_view'),
                    value: localizedNumber(summary.portfolios, locale),
                    detail: t('company_control.portfolios_in_view_help'),
                    icon: 'bi-building-check',
                    tone: 'ink',
                },
                {
                    label: t('company_control.needs_action'),
                    value: localizedNumber(summary.needs_action, locale),
                    detail: t('company_control.needs_action_help'),
                    icon: 'bi-exclamation-triangle',
                    tone: summary.needs_action > 0 ? 'red' : 'teal',
                },
                {
                    label: t('company_control.managed_value'),
                    value: valuations || t('company_control.no_value'),
                    detail: t('company_control.managed_value_help'),
                    icon: 'bi-bank',
                    tone: 'blue',
                },
                {
                    label: t('company_control.occupancy'),
                    value: percent(summary.occupancy_rate, locale),
                    detail: t('company_control.occupancy_help'),
                    icon: 'bi-buildings',
                    tone: 'teal',
                },
                {
                    label: t('company_control.arrears'),
                    value: arrears || t('company_control.no_arrears'),
                    detail: t('company_control.arrears_help'),
                    icon: 'bi-exclamation-circle',
                    tone: summary.currency_totals.some(
                        (position) => position.arrears > 0,
                    )
                        ? 'amber'
                        : 'teal',
                },
                {
                    label: t('company_control.open_requests'),
                    value: localizedNumber(summary.open_requests, locale),
                    detail: t('company_control.open_requests_help'),
                    icon: 'bi-tools',
                    tone: summary.open_requests > 0 ? 'amber' : 'teal',
                },
            ]}
        />
    );
}
