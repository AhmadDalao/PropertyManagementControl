import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency, currency } from '@/lib/utils';

import type { OperationsDashboardProps } from '../types';

export function OperationsMetrics({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t } = useTranslator();

    return (
        <MetricGrid
            metrics={[
                {
                    label: 'Managed assets',
                    value: props.stats.totalAssets,
                    detail:
                        props.mode === 'superadmin'
                            ? t('dashboard.portfolios_users', undefined, {
                                  portfolios: props.stats.totalPortfolios,
                                  users: props.stats.totalUsers,
                              })
                            : t('dashboard.vacant_units', undefined, {
                                  count: props.stats.vacantUnits,
                              }),
                    icon: 'bi-buildings',
                    tone: 'ink',
                    href: '/assets',
                },
                {
                    label: 'Portfolio value',
                    value: compactCurrency(
                        props.stats.totalValue,
                        props.app.locale,
                    ),
                    detail: t('dashboard.active_leases_count', undefined, {
                        count: props.stats.activeLeases,
                    }),
                    icon: 'bi-bank',
                    tone: 'blue',
                    href: '/assets',
                },
                {
                    label: 'Collected this month',
                    value: compactCurrency(
                        props.stats.monthlyRevenue,
                        props.app.locale,
                    ),
                    detail: t('dashboard.expenses_amount', undefined, {
                        amount: currency(props.stats.monthlyExpenses, locale),
                    }),
                    icon: 'bi-cash-stack',
                    tone: 'teal',
                    href: '/payments',
                },
                {
                    label: 'Outstanding rent',
                    value: compactCurrency(
                        props.stats.arrears,
                        props.app.locale,
                    ),
                    detail: t('dashboard.open_service_count', undefined, {
                        count: props.stats.openRequests,
                    }),
                    icon: 'bi-exclamation-circle',
                    tone: props.stats.arrears > 0 ? 'red' : 'amber',
                    href: '/reports',
                },
            ]}
        />
    );
}
