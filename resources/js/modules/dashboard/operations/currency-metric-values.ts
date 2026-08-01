import type { Translator } from '@/lib/i18n';
import {
    compactCurrency,
    currency,
    localizedNumber,
    percent,
} from '@/lib/utils';

import type { OperationsCurrencyPosition } from '../operations-types';

type MoneyField =
    | 'scheduledDue'
    | 'scheduledPaid'
    | 'revenue'
    | 'expenses'
    | 'net'
    | 'arrears';

export function currencyPositionCount(
    count: number,
    locale: string,
    t: Translator,
): string {
    return t('dashboard.currency_positions_count', undefined, {
        count: localizedNumber(count, locale),
    });
}

export function currencyPositionAmounts(
    positions: OperationsCurrencyPosition[],
    field: MoneyField,
    locale: string,
): string {
    return positions
        .map((position) => currency(position[field], locale, position.currency))
        .join(' · ');
}

export function currencyPositionRates(
    positions: OperationsCurrencyPosition[],
    locale: string,
): string {
    return positions
        .map(
            (position) =>
                `${position.currency} ${percent(position.collectionRate, locale)}`,
        )
        .join(' · ');
}

export function financialMetricValue(
    amount: number | null,
    positions: OperationsCurrencyPosition[],
    field: MoneyField,
    locale: string,
    t: Translator,
): { value: string; detail: string } {
    const single = positions.length === 1 ? positions[0] : null;

    if (amount !== null && single) {
        return {
            value: compactCurrency(amount, locale, single.currency),
            detail: currency(amount, locale, single.currency),
        };
    }

    return {
        value: currencyPositionCount(positions.length, locale, t),
        detail: currencyPositionAmounts(positions, field, locale),
    };
}
