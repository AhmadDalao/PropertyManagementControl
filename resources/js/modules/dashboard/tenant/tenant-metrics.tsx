import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency, currency, humanDate } from '@/lib/utils';

import type { TenantDashboardProps } from '../types';

export function TenantMetrics({ props }: { props: TenantDashboardProps }) {
    const { locale, t, text } = useTranslator();
    const lease = props.tenantPortal.lease;
    const currencyCode = lease?.currency ?? 'SAR';

    return (
        <MetricGrid
            metrics={[
                {
                    label: 'Lease',
                    value: props.stats.leaseCode ?? t('dashboard.not_active'),
                    detail: lease?.ends_at
                        ? t('dashboard.ends_on', undefined, {
                              date: humanDate(lease.ends_at, locale),
                          })
                        : t('dashboard.no_end_date'),
                    icon: 'bi-file-earmark-text',
                    tone: 'ink',
                },
                {
                    label: 'Days remaining',
                    value: props.stats.daysLeft ?? 0,
                    detail: text('In the current contract'),
                    icon: 'bi-calendar3',
                    tone: 'blue',
                },
                {
                    label: 'Paid',
                    value: compactCurrency(
                        props.stats.paidAmount,
                        props.app.locale,
                        currencyCode,
                    ),
                    detail: t('dashboard.posted_payments_count', undefined, {
                        count: props.tenantPortal.payments.length,
                    }),
                    icon: 'bi-check-circle',
                    tone: 'teal',
                },
                {
                    label: t('dashboard.due_now'),
                    value: compactCurrency(
                        props.stats.dueNow,
                        props.app.locale,
                        currencyCode,
                    ),
                    detail:
                        props.stats.overdue > 0
                            ? t('dashboard.overdue_amount', undefined, {
                                  amount: currency(
                                      props.stats.overdue,
                                      props.app.locale,
                                      currencyCode,
                                  ),
                              })
                            : lease?.next_due_date
                              ? t('dashboard.next_due_date', undefined, {
                                    date: humanDate(
                                        lease.next_due_date,
                                        props.app.locale,
                                    ),
                                })
                              : t('dashboard.no_amount_due'),
                    icon: 'bi-hourglass-split',
                    tone:
                        props.stats.overdue > 0
                            ? 'red'
                            : props.stats.dueNow > 0
                              ? 'amber'
                              : 'teal',
                },
            ]}
        />
    );
}
