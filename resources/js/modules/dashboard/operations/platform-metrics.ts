import type { WorkspaceMetric } from '@/components/operations';
import type { Translator } from '@/lib/i18n';
import { currency, localizedNumber, percent } from '@/lib/utils';

import type { OperationsDashboardProps } from '../types';
import {
    currencyPositionAmounts,
    financialMetricValue,
} from './currency-metric-values';
import { propertyFocusUrl } from './property-focus-url';

export function platformMetrics(
    props: OperationsDashboardProps,
    locale: string,
    t: Translator,
): WorkspaceMetric[] {
    const propertyId = props.propertyFocus.selected?.id;
    const occupied =
        Number(props.charts.occupancy.occupied ?? 0) +
        Number(props.charts.occupancy.partially_occupied ?? 0);
    const units = Object.values(props.charts.occupancy).reduce(
        (total, value) => total + Number(value),
        0,
    );
    const occupancyRate = units > 0 ? (occupied / units) * 100 : 0;
    const scheduled = financialMetricValue(
        props.financial.scheduledDue,
        props.financial.currencyTotals,
        'scheduledDue',
        locale,
        t,
    );
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
    const metricPeriod = t(`dashboard.metric_period_${props.period}`);

    return [
        {
            label: t('dashboard.total_properties'),
            value: localizedNumber(props.propertyFocus.property_count, locale),
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
                    : t('dashboard.active_leases_count', undefined, {
                          count: localizedNumber(
                              props.stats.activeLeases,
                              locale,
                          ),
                      }),
            icon: 'bi-buildings',
            tone: 'ink',
            href: propertyFocusUrl('/assets', propertyId),
        },
        {
            label: t('dashboard.total_units'),
            value: localizedNumber(units, locale),
            detail: t('dashboard.vacant_units', undefined, {
                count: localizedNumber(props.stats.vacantUnits, locale),
            }),
            icon: 'bi-door-open',
            tone: 'blue',
            href: propertyFocusUrl('/assets?rentable=1', propertyId),
        },
        {
            label: t('dashboard.occupancy_rate'),
            value: percent(occupancyRate, locale),
            detail: t('dashboard.occupied_units', undefined, {
                occupied: localizedNumber(occupied, locale),
                total: localizedNumber(units, locale),
            }),
            icon: 'bi-pie-chart',
            tone: occupancyRate >= 70 ? 'teal' : 'amber',
            href: propertyFocusUrl('/property-map', propertyId),
        },
        {
            label: t('dashboard.scheduled_rent'),
            value: scheduled.value,
            detail: t('dashboard.active_leases_count', undefined, {
                count: localizedNumber(props.stats.activeLeases, locale),
            }),
            icon: 'bi-file-earmark-text',
            tone: 'blue',
            href: propertyFocusUrl('/rent-collection', propertyId),
        },
        {
            label: t('dashboard.collected_for_period', undefined, {
                period: metricPeriod,
            }),
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
            detail: t('dashboard.overdue_rent_exposure', undefined, {
                installments: localizedNumber(
                    props.financial.overdueInstallments,
                    locale,
                ),
                leases: localizedNumber(props.financial.overdueLeases, locale),
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
