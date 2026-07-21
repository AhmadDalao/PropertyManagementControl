import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import { BreakdownBars, ReportRecordSection } from './report-visuals';
import type { ReportsPageProps } from './types';

export function ReportCollections({ props }: { props: ReportsPageProps }) {
    const { locale, t } = useTranslator();

    return (
        <>
            <div className="pmc-report-breakdown-grid is-single">
                <WorkspacePanel
                    eyebrow={t('reports.revenue_eyebrow')}
                    title={t('reports.monthly_collections')}
                    description={t('reports.monthly_collections_description')}
                >
                    <BreakdownBars
                        source={props.charts.revenueByMonth}
                        format={(value) => currency(value, locale, 'SAR')}
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
                                number: index + 1,
                            }),
                        meta: t('reports.lease_count', undefined, {
                            count: asset.lease_count,
                        }),
                        value: currency(asset.revenue, locale, asset.currency),
                        tone: 'success',
                    }))}
                />
            </div>
        </>
    );
}
