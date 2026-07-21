import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency } from '@/lib/utils';

import type { PortfolioInsights } from './types';

export function PortfolioMetrics({
    insights,
    locale,
}: {
    insights: PortfolioInsights;
    locale: string;
}) {
    const { t } = useTranslator();
    const amount = (value: number | null) =>
        value === null
            ? t('portfolios.mixed_currencies')
            : compactCurrency(value, locale, insights.currency);

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('portfolios.accounts'),
                    value: insights.total,
                    detail: t('portfolios.status_mix', undefined, {
                        active: insights.active,
                        archived: insights.archived,
                    }),
                    icon: 'bi-buildings',
                    tone: 'ink',
                },
                {
                    label: t('portfolios.managed_value'),
                    value: amount(insights.valuation_total),
                    detail: t('portfolios.property_records', undefined, {
                        count: insights.assets,
                    }),
                    icon: 'bi-bank',
                    tone: 'blue',
                },
                {
                    label: t('portfolios.net_position'),
                    value: amount(insights.net_total),
                    detail: t('portfolios.revenue_expenses', undefined, {
                        revenue: amount(insights.posted_revenue_total),
                        expenses: amount(insights.posted_expense_total),
                    }),
                    icon: 'bi-cash-stack',
                    tone:
                        insights.net_total !== null && insights.net_total < 0
                            ? 'red'
                            : 'teal',
                },
                {
                    label: t('portfolios.open_service'),
                    value: insights.open_maintenance,
                    detail: t('portfolios.users_in_scope', undefined, {
                        count: insights.users,
                    }),
                    icon: 'bi-tools',
                    tone: insights.open_maintenance > 0 ? 'amber' : 'teal',
                },
            ]}
        />
    );
}
