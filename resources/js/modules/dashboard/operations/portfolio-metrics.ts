import type { WorkspaceMetric } from '@/components/operations';
import type { Translator } from '@/lib/i18n';
import {
    compactCurrency,
    currency,
    localizedNumber,
    percent,
} from '@/lib/utils';

import type { OperationsDashboardProps } from '../types';
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

    return [
        {
            label: t('dashboard.rent_due_month'),
            value: compactCurrency(
                props.financial.scheduledDue,
                locale,
                props.financial.currency,
            ),
            detail: t('dashboard.rent_paid_month', undefined, {
                amount: currency(
                    props.financial.scheduledPaid,
                    locale,
                    props.financial.currency,
                ),
            }),
            icon: 'bi-calendar2-check',
            tone: 'ink',
            href: propertyFocusUrl(
                '/rent-collection?status=actionable',
                propertyId,
            ),
        },
        {
            label: t('dashboard.collection_rate'),
            value: percent(props.financial.collectionRate, locale),
            detail: t('dashboard.outstanding_amount', undefined, {
                amount: currency(
                    props.stats.arrears,
                    locale,
                    props.financial.currency,
                ),
            }),
            icon: 'bi-wallet2',
            tone: props.financial.collectionRate >= 80 ? 'teal' : 'amber',
            href: propertyFocusUrl(
                '/rent-collection?status=overdue',
                propertyId,
            ),
        },
        {
            label: t('dashboard.net_cash_flow'),
            value: compactCurrency(
                props.financial.net,
                locale,
                props.financial.currency,
            ),
            detail: t('dashboard.income_expense', undefined, {
                income: currency(
                    props.financial.revenue,
                    locale,
                    props.financial.currency,
                ),
                expense: currency(
                    props.financial.expenses,
                    locale,
                    props.financial.currency,
                ),
            }),
            icon: 'bi-graph-up-arrow',
            tone: props.financial.net >= 0 ? 'blue' : 'red',
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
