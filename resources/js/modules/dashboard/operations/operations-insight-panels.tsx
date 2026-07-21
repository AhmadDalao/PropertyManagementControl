import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import { operationsHealthScore } from '../metrics';
import { HealthSignals } from '../shared/health-signals';
import { DashboardRecordList } from '../shared/record-list';
import type { OperationsDashboardProps } from '../types';

export function OperationsInsightPanels({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { t, text } = useTranslator();
    const healthScore = operationsHealthScore(
        props.setupChecklist,
        props.stats,
    );
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
                eyebrow="Health"
                title={t('dashboard.operating_readiness', undefined, {
                    score: healthScore,
                })}
                description="Setup, occupancy, map, and contract signals."
            >
                <HealthSignals
                    signals={[
                        {
                            label: 'Setup',
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
                            label: 'Occupancy',
                            value: occupancyRate,
                            href: '/assets',
                        },
                        {
                            label: 'Map ready',
                            value: props.propertyMap.summary.coverage_percent,
                            href: '/property-map',
                        },
                    ]}
                />
            </WorkspacePanel>

            <WorkspacePanel
                eyebrow="Contracts"
                title="Lease expiry"
                description="Contracts ending within the next 90 days."
                action={{ label: 'Open leases', href: '/leases' }}
            >
                <DashboardRecordList
                    empty="No leases are expiring soon."
                    rows={props.expiringLeases.slice(0, 4).map((lease) => ({
                        href: `/leases/${lease.id}`,
                        title: lease.code,
                        meta: `${lease.tenant ?? text('No tenant')} · ${lease.asset ?? text('No asset')}`,
                        value: t('dashboard.days_count', undefined, {
                            count: lease.days_remaining ?? 0,
                        }),
                        tone:
                            Number(lease.days_remaining ?? 0) <= 30
                                ? 'danger'
                                : 'warning',
                    }))}
                />
            </WorkspacePanel>

            <WorkspacePanel
                eyebrow="Activity"
                title="Recent payments"
                description="Latest posted receipts in this scope."
                action={{ label: 'View all', href: '/payments' }}
            >
                <DashboardRecordList
                    empty="No payments have been posted."
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
