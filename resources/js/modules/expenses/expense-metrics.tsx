import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency, currency } from '@/lib/utils';

import type { ExpenseIndexPageProps } from './types';

type ExpenseMetricsProps = Pick<
    ExpenseIndexPageProps,
    'expenseInsights' | 'app'
>;

export function ExpenseMetrics({ expenseInsights, app }: ExpenseMetricsProps) {
    const { t } = useTranslator();
    const money = (amount: number | null, compact = true) => {
        if (amount === null || !expenseInsights.currency) {
            return t('expenses.mixed_currency_value', undefined, {
                count: expenseInsights.currency_count,
            });
        }

        return compact
            ? compactCurrency(amount, app.locale, expenseInsights.currency)
            : currency(amount, app.locale, expenseInsights.currency);
    };
    const pendingDetail =
        expenseInsights.pending_amount === null
            ? t('expenses.filter_portfolio_for_totals')
            : t('expenses.review_mix', undefined, {
                  amount: money(expenseInsights.pending_amount, false),
                  count: expenseInsights.unlinked_count,
              });

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('expenses.posted_expenses'),
                    value: money(expenseInsights.posted_amount),
                    detail: t('expenses.posted_entries', undefined, {
                        count: expenseInsights.posted_count,
                    }),
                    icon: 'bi-receipt',
                    tone: 'ink',
                },
                {
                    label: t('expenses.this_month'),
                    value: money(expenseInsights.posted_this_month),
                    detail: t('expenses.vendors', undefined, {
                        count: expenseInsights.vendors,
                    }),
                    icon: 'bi-calendar3',
                    tone: 'blue',
                },
                {
                    label: t('expenses.maintenance_cost'),
                    value: money(expenseInsights.maintenance_amount),
                    detail: t('expenses.linked_tickets', undefined, {
                        count: expenseInsights.linked_to_maintenance,
                    }),
                    icon: 'bi-tools',
                    tone: 'teal',
                },
                {
                    label: t('expenses.needs_review'),
                    value:
                        expenseInsights.pending_count +
                        expenseInsights.unlinked_count,
                    detail: pendingDetail,
                    icon: 'bi-exclamation-circle',
                    tone:
                        expenseInsights.pending_count +
                            expenseInsights.unlinked_count >
                        0
                            ? 'amber'
                            : 'teal',
                },
            ]}
        />
    );
}
