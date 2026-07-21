import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency, currency, percent } from '@/lib/utils';

import { ReportPulse } from './report-visuals';
import type { ReportsPageProps } from './types';

export function ReportOverview({ props }: { props: ReportsPageProps }) {
    const { locale, t } = useTranslator();
    const collectionRate = props.summary.collectionRate;

    return (
        <>
            <MetricGrid
                metrics={[
                    {
                        label: t('reports.collected'),
                        value: compactCurrency(
                            props.summary.revenue,
                            props.app.locale,
                        ),
                        detail: t(
                            'reports.collection_health_value',
                            undefined,
                            {
                                value: percent(collectionRate),
                            },
                        ),
                        icon: 'bi-cash-stack',
                        tone: 'ink',
                        href: '/payments',
                    },
                    {
                        label: t('reports.expenses'),
                        value: compactCurrency(
                            props.summary.expenses,
                            props.app.locale,
                        ),
                        detail: t('reports.recent_costs', undefined, {
                            count: props.recentExpenses.length,
                        }),
                        icon: 'bi-receipt',
                        tone: 'amber',
                        href: '/expenses',
                    },
                    {
                        label: t('reports.net_position'),
                        value: compactCurrency(
                            props.summary.net,
                            props.app.locale,
                        ),
                        detail: t('reports.occupancy_value', undefined, {
                            value: percent(props.summary.occupancyRate),
                        }),
                        icon: 'bi-graph-up-arrow',
                        tone: props.summary.net >= 0 ? 'teal' : 'red',
                    },
                    {
                        label: t('reports.arrears'),
                        value: compactCurrency(
                            props.summary.arrears,
                            props.app.locale,
                        ),
                        detail: t('reports.arrears_count', undefined, {
                            count: props.summary.leasesInArrears,
                        }),
                        icon: 'bi-exclamation-circle',
                        tone: props.summary.arrears > 0 ? 'red' : 'blue',
                        href: '/leases',
                    },
                ]}
            />

            <section className="pmc-report-pulse-grid">
                <ReportPulse
                    label={t('reports.collection_health')}
                    value={percent(collectionRate)}
                    detail={t('reports.scheduled_paid', undefined, {
                        paid: currency(props.summary.scheduledPaid, locale),
                        due: currency(props.summary.scheduledDue, locale),
                    })}
                    icon="bi-wallet2"
                    tone={collectionRate >= 80 ? 'good' : 'risk'}
                />
                <ReportPulse
                    label={t('reports.occupancy')}
                    value={percent(props.summary.occupancyRate)}
                    detail={t('reports.active_leases', undefined, {
                        count: props.summary.activeLeases,
                    })}
                    icon="bi-building-check"
                    tone={props.summary.occupancyRate >= 70 ? 'good' : 'warn'}
                />
                <ReportPulse
                    label={t('reports.service_backlog')}
                    value={props.summary.openRequests.toLocaleString()}
                    detail={t('reports.resolved_count', undefined, {
                        count: props.summary.resolvedRequests,
                    })}
                    icon="bi-tools"
                    tone={props.summary.openRequests > 0 ? 'warn' : 'good'}
                />
                <ReportPulse
                    label={t('reports.contracts_in_arrears')}
                    value={props.summary.leasesInArrears.toLocaleString()}
                    detail={t('reports.contract_balance', undefined, {
                        amount: currency(props.summary.contractBalance, locale),
                    })}
                    icon="bi-file-earmark-excel"
                    tone={props.summary.leasesInArrears > 0 ? 'risk' : 'good'}
                />
            </section>
        </>
    );
}
