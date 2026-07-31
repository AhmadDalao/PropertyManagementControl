import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency, localizedNumber, percent } from '@/lib/utils';

import { ReportPulse } from './report-visuals';
import type { OwnerStatementPageProps } from './types';

export function OwnerStatementSummary({
    props,
}: {
    props: OwnerStatementPageProps;
}) {
    const { locale, t } = useTranslator();

    return (
        <>
            <MetricGrid
                metrics={[
                    {
                        label: t('reports.collected'),
                        value: compactCurrency(props.summary.revenue, locale),
                        detail: t('reports.statement_posted_income'),
                        icon: 'bi-cash-stack',
                        tone: 'ink',
                    },
                    {
                        label: t('reports.expenses'),
                        value: compactCurrency(props.summary.expenses, locale),
                        detail: t('reports.statement_posted_costs'),
                        icon: 'bi-receipt',
                        tone: 'amber',
                    },
                    {
                        label: t('reports.net_position'),
                        value: compactCurrency(props.summary.net, locale),
                        detail: t('reports.statement_income_less_costs'),
                        icon: 'bi-graph-up-arrow',
                        tone: props.summary.net >= 0 ? 'teal' : 'red',
                    },
                    {
                        label: t('reports.arrears'),
                        value: compactCurrency(props.summary.arrears, locale),
                        detail: t('reports.arrears_count', undefined, {
                            count: localizedNumber(
                                props.summary.leasesInArrears,
                                locale,
                            ),
                        }),
                        icon: 'bi-exclamation-circle',
                        tone: props.summary.arrears > 0 ? 'red' : 'blue',
                    },
                ]}
            />

            <section className="pmc-statement-health-grid">
                <ReportPulse
                    label={t('reports.collection_health')}
                    value={percent(props.summary.collectionRate, locale)}
                    detail={t('reports.statement_collection_detail')}
                    icon="bi-wallet2"
                    tone={props.summary.collectionRate >= 80 ? 'good' : 'risk'}
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
                    detail={t('reports.statement_open_requests')}
                    icon="bi-tools"
                    tone={props.summary.openRequests > 0 ? 'warn' : 'good'}
                />
            </section>
        </>
    );
}
