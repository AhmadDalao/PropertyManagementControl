import type { WorkspaceMetric } from '@/components/operations';
import type { Translator } from '@/lib/i18n';
import { currency, localizedNumber, percent } from '@/lib/utils';

import type { OperationsDashboardProps } from '../types';
import {
    currencyPositionAmounts,
    currencyPositionCount,
    currencyPositionRates,
    financialMetricValue,
} from './currency-metric-values';
import { propertyFocusUrl } from './property-focus-url';

export function portfolioMetrics(
    props: OperationsDashboardProps,
    locale: string,
    t: Translator,
): WorkspaceMetric[] {
    const propertyId = props.propertyFocus.selected?.id;
    const occupied =
        Number(props.charts.occupancy.occupied ?? 0) +
        Number(props.charts.occupancy.partially_occupied ?? 0);
    const rentable = Object.values(props.charts.occupancy).reduce(
        (total, value) => total + Number(value),
        0,
    );
    const occupancyRate = rentable > 0 ? (occupied / rentable) * 100 : 0;
    const rentDue = financialMetricValue(
        props.financial.scheduledDue,
        props.financial.currencyTotals,
        'scheduledDue',
        locale,
        t,
    );
    const net = financialMetricValue(
        props.financial.net,
        props.financial.currencyTotals,
        'net',
        locale,
        t,
    );
    const collectionRate = props.financial.collectionRate;

    return [
        {
            label: t('dashboard.rent_due_month'),
            value: rentDue.value,
            detail:
                props.financial.currencyCount === 1
                    ? t('dashboard.rent_paid_month', undefined, {
                          amount: currency(
                              props.financial.scheduledPaid ?? 0,
                              locale,
                              props.financial.currency ?? 'SAR',
                          ),
                      })
                    : currencyPositionAmounts(
                          props.financial.currencyTotals,
                          'scheduledDue',
                          locale,
                      ),
            icon: 'bi-calendar2-check',
            tone: 'ink',
            href: propertyFocusUrl(
                '/rent-collection?status=actionable',
                propertyId,
            ),
        },
        {
            label: t('dashboard.collection_rate'),
            value:
                collectionRate === null
                    ? currencyPositionCount(
                          props.financial.currencyCount,
                          locale,
                          t,
                      )
                    : percent(collectionRate, locale),
            detail:
                collectionRate === null
                    ? currencyPositionRates(
                          props.financial.currencyTotals,
                          locale,
                      )
                    : t('dashboard.outstanding_amount', undefined, {
                          amount: currency(
                              props.financial.arrears ?? 0,
                              locale,
                              props.financial.currency ?? 'SAR',
                          ),
                      }),
            icon: 'bi-wallet2',
            tone:
                collectionRate !== null && collectionRate >= 80
                    ? 'teal'
                    : 'amber',
            href: propertyFocusUrl(
                '/rent-collection?status=overdue',
                propertyId,
            ),
        },
        {
            label: t('dashboard.net_cash_flow'),
            value: net.value,
            detail:
                props.financial.currencyCount === 1
                    ? t('dashboard.income_expense', undefined, {
                          income: currency(
                              props.financial.revenue ?? 0,
                              locale,
                              props.financial.currency ?? 'SAR',
                          ),
                          expense: currency(
                              props.financial.expenses ?? 0,
                              locale,
                              props.financial.currency ?? 'SAR',
                          ),
                      })
                    : currencyPositionAmounts(
                          props.financial.currencyTotals,
                          'net',
                          locale,
                      ),
            icon: 'bi-graph-up-arrow',
            tone: props.financial.currencyTotals.some(
                (position) => position.net < 0,
            )
                ? 'red'
                : 'blue',
            href: propertyId
                ? `/reports/properties/${propertyId}?tab=overview`
                : '/reports?tab=overview',
        },
        {
            label: t('dashboard.occupancy_rate'),
            value: percent(occupancyRate, locale),
            detail: t('dashboard.occupied_units', undefined, {
                occupied: localizedNumber(occupied, locale),
                total: localizedNumber(rentable, locale),
            }),
            icon: 'bi-building-check',
            tone: occupancyRate >= 70 ? 'teal' : 'amber',
            href: propertyFocusUrl('/assets?rentable=1', propertyId),
        },
    ];
}
