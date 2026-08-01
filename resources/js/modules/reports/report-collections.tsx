import { MetricGrid, WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate, localizedNumber } from '@/lib/utils';

import { BreakdownBars, ReportRecordSection } from './report-visuals';
import type { ReportDataProps } from './types';

export function ReportCollections({ props }: { props: ReportDataProps }) {
    const { locale, t } = useTranslator();

    return (
        <>
            <MetricGrid
                metrics={[
                    {
                        label: t('reports.open_collection_accounts'),
                        value: localizedNumber(
                            props.summary.openCollectionCount,
                            locale,
                        ),
                        detail: t('reports.open_collection_accounts_help'),
                        icon: 'bi-wallet2',
                        tone: 'ink',
                        href: collectionHref(props, 'open'),
                    },
                    {
                        label: t('reports.untracked_overdue_accounts'),
                        value: localizedNumber(
                            props.summary.untrackedOverdueCount,
                            locale,
                        ),
                        detail: t('reports.untracked_overdue_accounts_help'),
                        icon: 'bi-telephone-forward',
                        tone:
                            props.summary.untrackedOverdueCount > 0
                                ? 'amber'
                                : 'teal',
                        href: collectionHref(props, 'overdue', 'untracked'),
                    },
                    {
                        label: t('reports.follow_ups_due'),
                        value: localizedNumber(
                            props.summary.followUpDueCount,
                            locale,
                        ),
                        detail: t('reports.follow_ups_due_help'),
                        icon: 'bi-calendar-check',
                        tone:
                            props.summary.followUpDueCount > 0
                                ? 'amber'
                                : 'teal',
                        href: collectionHref(props, 'open', 'due'),
                    },
                    {
                        label: t('reports.broken_promises'),
                        value: localizedNumber(
                            props.summary.brokenPromisesCount,
                            locale,
                        ),
                        detail: t('reports.broken_promises_help'),
                        icon: 'bi-exclamation-circle',
                        tone:
                            props.summary.brokenPromisesCount > 0
                                ? 'red'
                                : 'teal',
                        href: collectionHref(props, 'open', 'broken'),
                    },
                ]}
            />

            <div className="pmc-report-breakdown-grid is-single">
                <WorkspacePanel
                    eyebrow={t('reports.revenue_eyebrow')}
                    title={t('reports.monthly_collections')}
                    description={t('reports.monthly_collections_description')}
                >
                    <BreakdownBars
                        source={props.charts.revenueByMonth}
                        formatLabel={(label) => label}
                    />
                </WorkspacePanel>
            </div>
            <div className="pmc-report-record-grid">
                <ReportRecordSection
                    title={t('reports.contracts_in_arrears')}
                    description={t('reports.arrears_description')}
                    empty={t('reports.no_arrears')}
                    rows={props.arrearsLeases.map((lease) => ({
                        href: `/leases/${lease.id}`,
                        title: lease.code,
                        meta: `${lease.tenant ?? t('reports.no_tenant')} · ${lease.asset ?? t('reports.no_asset')}`,
                        value: currency(
                            lease.arrears_amount,
                            locale,
                            lease.currency,
                        ),
                        tone: 'danger',
                    }))}
                />
                <ReportRecordSection
                    title={t('reports.recent_payments')}
                    description={t('reports.recent_payments_description')}
                    empty={t('reports.no_recent_payments')}
                    rows={props.recentPayments.map((payment) => ({
                        href: `/payments/${payment.id}`,
                        title: payment.reference,
                        meta: `${payment.tenant ?? t('reports.no_tenant')} · ${humanDate(payment.received_on, locale)}`,
                        value: currency(
                            payment.amount,
                            locale,
                            payment.currency,
                        ),
                        tone: 'success',
                    }))}
                />
                <ReportRecordSection
                    title={t('reports.top_assets')}
                    description={t('reports.top_assets_description')}
                    empty={t('reports.no_top_assets')}
                    rows={props.topAssets.map((asset, index) => ({
                        href: `/assets/${asset.id}`,
                        title:
                            asset.asset ||
                            t('reports.asset_number', undefined, {
                                number: localizedNumber(index + 1, locale),
                            }),
                        meta: t('reports.lease_count', undefined, {
                            count: localizedNumber(asset.lease_count, locale),
                        }),
                        value: currency(asset.revenue, locale, asset.currency),
                        tone: 'success',
                    }))}
                />
            </div>
        </>
    );
}

function collectionHref(
    props: ReportDataProps,
    status: string,
    followUp?: string,
): string {
    const query = new URLSearchParams({ status });

    if (followUp) {
        query.set('follow_up', followUp);
    }

    if (props.filters.portfolio_id) {
        query.set('portfolio_id', String(props.filters.portfolio_id));
    }

    if (props.filters.property_id) {
        query.set('property_id', String(props.filters.property_id));
    }

    return `/rent-collection?${query.toString()}`;
}
