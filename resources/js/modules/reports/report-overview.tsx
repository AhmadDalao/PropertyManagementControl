import { useTranslator } from '@/lib/i18n';
import { localizedNumber, percent } from '@/lib/utils';

import { CurrencyPositionGrid } from './currency-position-grid';
import { ReportComparison } from './report-comparison';
import { ReportPulse } from './report-visuals';
import type { ReportDataProps } from './types';

export function ReportOverview({ props }: { props: ReportDataProps }) {
    const { locale, t } = useTranslator();
    const collectionRate = props.summary.collectionRate;

    return (
        <>
            <CurrencyPositionGrid positions={props.summary.currencyTotals} />
            <ReportComparison comparison={props.comparison} />

            <section className="pmc-report-pulse-grid">
                <ReportPulse
                    label={t('reports.collection_health')}
                    value={
                        collectionRate === null
                            ? localizedNumber(
                                  props.summary.currencyCount,
                                  locale,
                              )
                            : percent(collectionRate, locale)
                    }
                    detail={
                        collectionRate === null
                            ? t('reports.currency_count', undefined, {
                                  count: localizedNumber(
                                      props.summary.currencyCount,
                                      locale,
                                  ),
                              })
                            : t('reports.collection_health_value', undefined, {
                                  value: percent(collectionRate, locale),
                              })
                    }
                    icon="bi-wallet2"
                    tone={
                        collectionRate !== null && collectionRate >= 80
                            ? 'good'
                            : 'risk'
                    }
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
                    detail={t('reports.currency_safe_totals')}
                    icon="bi-file-earmark-excel"
                    tone={props.summary.leasesInArrears > 0 ? 'risk' : 'good'}
                />
            </section>
        </>
    );
}
