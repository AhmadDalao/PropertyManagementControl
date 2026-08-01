import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import {
    compactCurrency,
    currency,
    localizedNumber,
    percent,
} from '@/lib/utils';

import { ReportPulse } from './report-visuals';
import type { ReportDataProps } from './types';

export function ReportOverview({
    props,
    links,
}: {
    props: ReportDataProps;
    links?: {
        payments?: string;
        expenses?: string;
        leases?: string;
    };
}) {
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
                                value: percent(collectionRate, locale),
                            },
                        ),
                        icon: 'bi-cash-stack',
                        tone: 'ink',
                        href: links?.payments ?? '/payments',
                    },
                    {
                        label: t('reports.expenses'),
                        value: compactCurrency(
                            props.summary.expenses,
                            props.app.locale,
                        ),
                        detail: t('reports.recent_costs', undefined, {
                            count: localizedNumber(
                                props.recentExpenses.length,
                                locale,
                            ),
                        }),
                        icon: 'bi-receipt',
                        tone: 'amber',
                        href: links?.expenses ?? '/expenses',
                    },
                    {
                        label: t('reports.net_position'),
                        value: compactCurrency(
                            props.summary.net,
                            props.app.locale,
                        ),
                        detail: t('reports.occupancy_value', undefined, {
                            value: percent(props.summary.occupancyRate, locale),
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
                            count: localizedNumber(
                                props.summary.leasesInArrears,
                                locale,
                            ),
                        }),
                        icon: 'bi-exclamation-circle',
                        tone: props.summary.arrears > 0 ? 'red' : 'blue',
                        href: links?.leases ?? '/leases',
                    },
                ]}
            />

            <section className="pmc-report-pulse-grid">
                <ReportPulse
                    label={t('reports.collection_health')}
                    value={percent(collectionRate, locale)}
                    detail={t('reports.scheduled_paid', undefined, {
                        paid: currency(props.summary.scheduledPaid, locale),
                        due: currency(props.summary.scheduledDue, locale),
                    })}
                    icon="bi-wallet2"
                    tone={collectionRate >= 80 ? 'good' : 'risk'}
                />
                <ReportPulse
                    label={t('reports.occupancy')}
                    value={percent(props.summary.occupancyRate, locale)}
                    detail={t('reports.active_leases', undefined, {
                        count: localizedNumber(
                            props.summary.activeLeases,
                            locale,
                        ),
                    })}
                    icon="bi-building-check"
                    tone={props.summary.occupancyRate >= 70 ? 'good' : 'warn'}
                />
                <ReportPulse
                    label={t('reports.service_backlog')}
                    value={localizedNumber(props.summary.openRequests, locale)}
                    detail={t('reports.resolved_count', undefined, {
                        count: localizedNumber(
                            props.summary.resolvedRequests,
                            locale,
                        ),
                    })}
                    icon="bi-tools"
                    tone={props.summary.openRequests > 0 ? 'warn' : 'good'}
                />
                <ReportPulse
                    label={t('reports.contracts_in_arrears')}
                    value={localizedNumber(
                        props.summary.leasesInArrears,
                        locale,
                    )}
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
