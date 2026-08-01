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
    const money = (value: number | null, field: 'arrears' | 'net') =>
        value !== null && summary.currency
            ? compactCurrency(value, locale, summary.currency)
            : summary.currency_totals
                  .map((position) =>
                      compactCurrency(
                          position[field],
                          locale,
                          position.currency,
                      ),
                  )
                  .join(' · ');

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
                    value:
                        summary.collection_rate === null
                            ? t(
                                  'portfolio_control.currency_positions_count',
                                  undefined,
                                  {
                                      count: localizedNumber(
                                          summary.currency_count,
                                          locale,
                                      ),
                                  },
                              )
                            : percent(summary.collection_rate, locale),
                    detail: t('portfolio_control.collection_help'),
                    icon: 'bi-cash-stack',
                    tone: 'blue',
                },
                {
                    label: t('portfolio_control.arrears'),
                    value: money(summary.arrears, 'arrears'),
                    detail: t('portfolio_control.arrears_help'),
                    icon: 'bi-exclamation-circle',
                    tone: summary.currency_totals.some(
                        (position) => position.arrears > 0,
                    )
                        ? 'amber'
                        : 'teal',
                },
                {
                    label: t('portfolio_control.net_cash_flow'),
                    value: money(summary.net, 'net'),
                    detail: t('portfolio_control.net_cash_flow_help'),
                    icon: 'bi-graph-up-arrow',
                    tone: summary.currency_totals.some(
                        (position) => position.net < 0,
                    )
                        ? 'red'
                        : 'teal',
                },
            ]}
        />
    );
}
