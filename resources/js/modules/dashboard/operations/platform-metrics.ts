import type { WorkspaceMetric } from '@/components/operations';
import type { Translator } from '@/lib/i18n';
import { compactCurrency, currency, localizedNumber } from '@/lib/utils';

import type { OperationsDashboardProps } from '../types';
import { propertyFocusUrl } from './property-focus-url';

export function platformMetrics(
    props: OperationsDashboardProps,
    locale: string,
    t: Translator,
): WorkspaceMetric[] {
    const propertyId = props.propertyFocus.selected?.id;

    return [
        {
            label: t('dashboard.managed_assets'),
            value: localizedNumber(props.stats.totalAssets, locale),
            detail:
                props.mode === 'superadmin' && !propertyId
                    ? t('dashboard.portfolios_users', undefined, {
                          portfolios: localizedNumber(
                              props.stats.totalPortfolios,
                              locale,
                          ),
                          users: localizedNumber(
                              props.stats.totalUsers,
                              locale,
                          ),
                      })
                    : t('dashboard.vacant_units', undefined, {
                          count: localizedNumber(
                              props.stats.vacantUnits,
                              locale,
                          ),
                      }),
            icon: 'bi-buildings',
            tone: 'ink',
            href: propertyFocusUrl('/assets', propertyId),
        },
        {
            label: t('dashboard.portfolio_value'),
            value: compactCurrency(props.stats.totalValue, locale),
            detail: t('dashboard.active_leases_count', undefined, {
                count: localizedNumber(props.stats.activeLeases, locale),
            }),
            icon: 'bi-bank',
            tone: 'blue',
            href: propertyFocusUrl('/assets', propertyId),
        },
        {
            label: t('dashboard.collected_this_month'),
            value: compactCurrency(props.stats.monthlyRevenue, locale),
            detail: t('dashboard.expenses_amount', undefined, {
                amount: currency(props.stats.monthlyExpenses, locale),
            }),
            icon: 'bi-cash-stack',
            tone: 'teal',
            href: propertyFocusUrl('/payments', propertyId),
        },
        {
            label: t('dashboard.outstanding_rent'),
            value: compactCurrency(props.stats.arrears, locale),
            detail: t('dashboard.open_service_count', undefined, {
                count: localizedNumber(props.stats.openRequests, locale),
            }),
            icon: 'bi-exclamation-circle',
            tone: props.stats.arrears > 0 ? 'red' : 'amber',
            href: propertyFocusUrl(
                '/rent-collection?status=overdue',
                propertyId,
            ),
        },
    ];
}
