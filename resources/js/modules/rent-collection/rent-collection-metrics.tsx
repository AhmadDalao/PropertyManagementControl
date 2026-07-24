import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import {
    compactCurrency,
    currency,
    localizedNumber,
    percent,
} from '@/lib/utils';

import type { RentCollectionPageProps } from './types';

type RentCollectionMetricsProps = Pick<
    RentCollectionPageProps,
    'collectionInsights' | 'app'
>;

export function RentCollectionMetrics({
    collectionInsights,
    app,
}: RentCollectionMetricsProps) {
    const { t } = useTranslator();
    const money = (amount: number) =>
        collectionInsights.mixed_currencies
            ? t('rent_collection.mixed_currencies')
            : compactCurrency(amount, app.locale, collectionInsights.currency);

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('rent_collection.outstanding_balance'),
                    value: money(collectionInsights.outstanding_amount),
                    detail: t('rent_collection.open_obligations', undefined, {
                        count: localizedNumber(
                            collectionInsights.open_count,
                            app.locale,
                        ),
                    }),
                    icon: 'bi-wallet2',
                    tone: 'ink',
                    href: '/rent-collection?status=open',
                },
                {
                    label: t('rent_collection.overdue_balance'),
                    value: money(collectionInsights.overdue_amount),
                    detail: t(
                        'rent_collection.overdue_obligations',
                        undefined,
                        {
                            count: localizedNumber(
                                collectionInsights.overdue_count,
                                app.locale,
                            ),
                        },
                    ),
                    icon: 'bi-exclamation-circle',
                    tone:
                        collectionInsights.overdue_amount > 0 ? 'red' : 'teal',
                    href: '/rent-collection?status=overdue',
                },
                {
                    label: t('rent_collection.due_next_30'),
                    value: money(collectionInsights.due_next_30_amount),
                    detail: t('rent_collection.prepare_collection'),
                    icon: 'bi-calendar2-check',
                    tone: 'amber',
                    href: `/rent-collection?status=actionable&date_from=${today()}&date_to=${inThirtyDays()}`,
                },
                {
                    label: t('rent_collection.month_collection_rate'),
                    value: percent(
                        collectionInsights.collection_rate,
                        app.locale,
                    ),
                    detail: collectionInsights.mixed_currencies
                        ? t('rent_collection.mixed_currencies')
                        : t(
                              'rent_collection.month_paid_against_due',
                              undefined,
                              {
                                  paid: currency(
                                      collectionInsights.paid_this_month,
                                      app.locale,
                                      collectionInsights.currency,
                                  ),
                                  due: currency(
                                      collectionInsights.scheduled_this_month,
                                      app.locale,
                                      collectionInsights.currency,
                                  ),
                              },
                          ),
                    icon: 'bi-graph-up-arrow',
                    tone:
                        collectionInsights.collection_rate >= 80
                            ? 'teal'
                            : 'amber',
                },
            ]}
        />
    );
}

function today(): string {
    return localDate(new Date());
}

function inThirtyDays(): string {
    const date = new Date();
    date.setDate(date.getDate() + 30);

    return localDate(date);
}

function localDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}
