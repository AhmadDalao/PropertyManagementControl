import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency, localizedNumber, percent } from '@/lib/utils';

import type { PortfolioControlSummary } from './types';

export function PortfolioControlMetrics({
    summary,
}: {
    summary: PortfolioControlSummary;
}) {
    const { locale, t } = useTranslator();
    const money = (value: number) =>
        summary.currency
            ? compactCurrency(value, locale, summary.currency)
            : t('portfolios.mixed_currencies');

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('portfolio_control.properties_in_view'),
                    value: localizedNumber(summary.properties, locale),
                    detail: t('portfolio_control.properties_in_view_help'),
                    icon: 'bi-buildings',
                    tone: 'ink',
                },
                {
                    label: t('portfolio_control.needs_action'),
                    value: localizedNumber(summary.risk, locale),
                    detail: t('portfolio_control.needs_action_help'),
                    icon: 'bi-exclamation-triangle',
                    tone: summary.risk > 0 ? 'red' : 'teal',
                },
                {
                    label: t('portfolio_control.occupancy'),
                    value: percent(summary.occupancy_rate, locale),
                    detail: t('portfolio_control.occupancy_help'),
                    icon: 'bi-building-check',
                    tone: 'teal',
                },
                {
                    label: t('portfolio_control.collection'),
                    value: percent(summary.collection_rate, locale),
                    detail: t('portfolio_control.collection_help'),
                    icon: 'bi-cash-stack',
                    tone: 'blue',
                },
                {
                    label: t('portfolio_control.arrears'),
                    value: money(summary.arrears),
                    detail: t('portfolio_control.arrears_help'),
                    icon: 'bi-exclamation-circle',
                    tone: summary.arrears > 0 ? 'amber' : 'teal',
                },
                {
                    label: t('portfolio_control.net_cash_flow'),
                    value: money(summary.net),
                    detail: t('portfolio_control.net_cash_flow_help'),
                    icon: 'bi-graph-up-arrow',
                    tone: summary.net < 0 ? 'red' : 'teal',
                },
            ]}
        />
    );
}
