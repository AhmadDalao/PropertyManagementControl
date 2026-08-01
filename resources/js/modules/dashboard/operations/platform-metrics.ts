import type { WorkspaceMetric } from '@/components/operations';
import type { Translator } from '@/lib/i18n';
import { compactCurrency, currency, localizedNumber } from '@/lib/utils';

import type { OperationsDashboardProps } from '../types';
import {
    currencyPositionAmounts,
    currencyPositionCount,
    financialMetricValue,
} from './currency-metric-values';
import { propertyFocusUrl } from './property-focus-url';

export function platformMetrics(
    props: OperationsDashboardProps,
    locale: string,
    t: Translator,
): WorkspaceMetric[] {
    const propertyId = props.propertyFocus.selected?.id;
    const valuationSingle =
        props.stats.valuationTotals.length === 1
            ? props.stats.valuationTotals[0]
            : null;
    const valuationValue =
        props.stats.totalValue !== null && valuationSingle
            ? compactCurrency(
                  props.stats.totalValue,
                  locale,
                  valuationSingle.currency,
              )
            : currencyPositionCount(
                  props.stats.valuationTotals.length,
                  locale,
                  t,
              );
    const valuationDetail = valuationSingle
        ? t('dashboard.active_leases_count', undefined, {
              count: localizedNumber(props.stats.activeLeases, locale),
          })
        : props.stats.valuationTotals
              .map((position) =>
                  currency(position.amount, locale, position.currency),
              )
              .join(' · ');
    const revenue = financialMetricValue(
        props.financial.revenue,
        props.financial.currencyTotals,
        'revenue',
        locale,
        t,
    );
    const arrears = financialMetricValue(
        props.financial.arrears,
        props.financial.currencyTotals,
        'arrears',
        locale,
        t,
    );

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
            value: valuationValue,
            detail: valuationDetail,
            icon: 'bi-bank',
            tone: 'blue',
            href: propertyFocusUrl('/assets', propertyId),
        },
        {
            label: t('dashboard.collected_this_month'),
            value: revenue.value,
            detail:
                props.financial.currencyCount === 1
                    ? t('dashboard.expenses_amount', undefined, {
                          amount: currency(
                              props.financial.expenses ?? 0,
                              locale,
                              props.financial.currency ?? 'SAR',
                          ),
                      })
                    : currencyPositionAmounts(
                          props.financial.currencyTotals,
                          'revenue',
                          locale,
                      ),
            icon: 'bi-cash-stack',
            tone: 'teal',
            href: propertyFocusUrl('/payments', propertyId),
        },
        {
            label: t('dashboard.outstanding_rent'),
            value: arrears.value,
            detail: t('dashboard.open_service_count', undefined, {
                count: localizedNumber(props.stats.openRequests, locale),
            }),
            icon: 'bi-exclamation-circle',
            tone: props.financial.hasArrears ? 'red' : 'amber',
            href: propertyFocusUrl(
                '/rent-collection?status=overdue',
                propertyId,
            ),
        },
    ];
}
