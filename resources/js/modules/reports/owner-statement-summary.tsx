import { useTranslator } from '@/lib/i18n';
import { localizedNumber, percent } from '@/lib/utils';

import { CurrencyPositionGrid } from './currency-position-grid';
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
            <CurrencyPositionGrid positions={props.summary.currencyTotals} />

            <section className="pmc-statement-health-grid">
                <ReportPulse
                    label={t('reports.collection_health')}
                    value={
                        props.summary.collectionRate === null
                            ? localizedNumber(
                                  props.summary.currencyCount,
                                  locale,
                              )
                            : percent(props.summary.collectionRate, locale)
                    }
                    detail={
                        props.summary.collectionRate === null
                            ? t('reports.currency_count', undefined, {
                                  count: localizedNumber(
                                      props.summary.currencyCount,
                                      locale,
                                  ),
                              })
                            : t('reports.statement_collection_detail')
                    }
                    icon="bi-wallet2"
                    tone={
                        props.summary.collectionRate !== null &&
                        props.summary.collectionRate >= 80
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
                    detail={t('reports.statement_open_requests')}
                    icon="bi-tools"
                    tone={props.summary.openRequests > 0 ? 'warn' : 'good'}
                />
            </section>
        </>
    );
}
