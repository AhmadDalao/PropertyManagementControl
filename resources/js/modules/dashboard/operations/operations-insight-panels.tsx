import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate, localizedNumber } from '@/lib/utils';

import { HealthSignals } from '../shared/health-signals';
import { DashboardRecordList } from '../shared/record-list';
import type { OperationsDashboardProps } from '../types';

export function OperationsInsightPanels({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t, text } = useTranslator();
    const completedSetup = props.setupChecklist.filter(
        (item) => item.done,
    ).length;
    const occupiedAssets =
        Number(props.charts.occupancy.occupied ?? 0) +
        Number(props.charts.occupancy.partially_occupied ?? 0);
    const occupancyTotal = Object.values(props.charts.occupancy).reduce(
        (total, value) => total + Number(value),
        0,
    );
    const occupancyRate =
        occupancyTotal > 0
            ? Math.round((occupiedAssets / occupancyTotal) * 100)
            : 0;

    return (
        <div className="pmc-command-grid is-three">
            <WorkspacePanel
                eyebrow={t('dashboard.portfolio_health')}
                title={t('dashboard.portfolio_signals')}
                description={t('dashboard.portfolio_signals_description')}
            >
                <HealthSignals
                    signals={[
                        {
                            label: t('dashboard.setup_completion'),
                            value:
                                props.setupChecklist.length > 0
                                    ? Math.round(
                                          (completedSetup /
                                              props.setupChecklist.length) *
                                              100,
                                      )
                                    : 100,
                            href: '/documentation',
                        },
                        {
                            label: t('dashboard.occupancy_rate'),
                            value: occupancyRate,
                            href: '/assets',
                        },
                        {
                            label: t('dashboard.map_coverage'),
                            value: props.propertyMap.summary.coverage_percent,
                            href: '/property-map',
                        },
                    ]}
                />
            </WorkspacePanel>

            <WorkspacePanel
                eyebrow={t('dashboard.contracts')}
                title={t('dashboard.lease_expiry')}
                description={t('dashboard.lease_expiry_description')}
                action={{
                    label: t('dashboard.open_expiry_report'),
                    href: '/reports?tab=operations',
                }}
            >
                <DashboardRecordList
                    empty={t('dashboard.no_expiring_leases')}
                    rows={props.expiringLeases.slice(0, 4).map((lease) => ({
                        href: `/leases/${lease.id}`,
                        title: lease.code,
                        meta: `${lease.tenant ?? text('No tenant')} · ${lease.asset ?? text('No asset')}`,
                        value: t('dashboard.days_count', undefined, {
                            count: localizedNumber(
                                lease.days_remaining ?? 0,
                                locale,
                            ),
                        }),
                        tone:
                            Number(lease.days_remaining ?? 0) <= 30
                                ? 'danger'
                                : 'warning',
                    }))}
                />
            </WorkspacePanel>

            <WorkspacePanel
                eyebrow={t('dashboard.activity')}
                title={t('dashboard.recent_payments')}
                description={t('dashboard.recent_payments_description')}
                action={{
                    label: t('actions.view_all'),
                    href: '/payments',
                }}
            >
                <DashboardRecordList
                    empty={t('dashboard.no_recent_payments')}
                    rows={props.recentPayments.slice(0, 4).map((payment) => ({
                        href: `/payments/${payment.id}`,
                        title:
                            payment.tenant_profile?.user?.name ??
                            t('payments.payment_number', undefined, {
                                id: payment.id,
                            }),
                        meta: humanDate(payment.received_on, props.app.locale),
                        value: currency(
                            payment.amount,
                            props.app.locale,
                            payment.currency,
                        ),
                        tone: 'success',
                    }))}
                />
            </WorkspacePanel>
        </div>
    );
}
