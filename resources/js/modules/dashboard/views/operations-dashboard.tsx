import { Head, Link } from '@inertiajs/react';

import {
    MetricGrid,
    StatusBadge,
    WorkspaceHeader,
    WorkspacePanel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency, humanDate } from '@/lib/utils';

import { operationsHealthScore } from '../metrics';
import type { DashboardPageProps, NextAction } from '../types';

export function OperationsDashboard({ props }: { props: DashboardPageProps }) {
    const setupChecklist = props.setupChecklist ?? [];
    const nextActions = props.nextActions ?? [];
    const recentPayments = props.recentPayments ?? [];
    const recentMaintenance = props.recentMaintenance ?? [];
    const arrearsLeases = props.arrearsLeases ?? [];
    const expiringLeases = props.expiringLeases ?? [];
    const mapSummary = props.propertyMap?.summary;
    const healthScore = operationsHealthScore(setupChecklist, props.stats);
    const completedSetup = setupChecklist.filter((item) => item.done).length;
    const occupancy = props.charts?.occupancy ?? {};
    const occupiedAssets = Number(occupancy.occupied ?? 0);
    const occupancyTotal = Object.values(occupancy).reduce(
        (total, value) => total + Number(value),
        0,
    );
    const occupancyRate =
        occupancyTotal > 0
            ? Math.round((occupiedAssets / occupancyTotal) * 100)
            : 0;

    return (
        <AdminLayout>
            <Head title="Dashboard" />

            <WorkspaceHeader
                eyebrow={
                    props.mode === 'superadmin'
                        ? 'Platform overview'
                        : 'Portfolio overview'
                }
                title="Property operations, at a glance."
                description="See the money, occupancy, contracts, and service work that need attention today."
                actions={[
                    {
                        label: 'Create asset',
                        href: '/assets/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                    {
                        label: 'Create tenant',
                        href: '/tenants/create',
                        icon: 'bi-person-plus',
                    },
                    {
                        label: 'Reports',
                        href: '/reports',
                        icon: 'bi-bar-chart-line',
                        tone: 'quiet',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Managed assets',
                        value: props.stats.totalAssets ?? 0,
                        detail:
                            props.mode === 'superadmin'
                                ? `${props.stats.totalPortfolios ?? 0} portfolios · ${props.stats.totalUsers ?? 0} users`
                                : `${props.stats.vacantUnits ?? 0} vacant rentable units`,
                        icon: 'bi-buildings',
                        tone: 'ink',
                        href: '/assets',
                    },
                    {
                        label: 'Portfolio value',
                        value: currency(
                            Number(props.stats.totalValue ?? 0),
                            props.app.locale,
                        ),
                        detail: `${props.stats.activeLeases ?? 0} active leases`,
                        icon: 'bi-bank',
                        tone: 'blue',
                        href: '/assets',
                    },
                    {
                        label: 'Collected this month',
                        value: currency(
                            Number(props.stats.monthlyRevenue ?? 0),
                            props.app.locale,
                        ),
                        detail: `${currency(Number(props.stats.monthlyExpenses ?? 0), props.app.locale)} expenses`,
                        icon: 'bi-cash-stack',
                        tone: 'teal',
                        href: '/payments',
                    },
                    {
                        label: 'Outstanding rent',
                        value: currency(
                            Number(props.stats.arrears ?? 0),
                            props.app.locale,
                        ),
                        detail: `${props.stats.openRequests ?? 0} open service requests`,
                        icon: 'bi-exclamation-circle',
                        tone:
                            Number(props.stats.arrears ?? 0) > 0
                                ? 'red'
                                : 'amber',
                        href: '/reports',
                    },
                ]}
            />

            <ActionQueue actions={nextActions} />

            <div className="pmc-command-grid">
                <WorkspacePanel
                    eyebrow="Collections"
                    title="Outstanding balances"
                    description="Largest balances that should be reviewed first."
                    action={{
                        label: 'Open payments',
                        href: '/payments',
                    }}
                >
                    <RecordList
                        empty="No outstanding balances."
                        rows={arrearsLeases.slice(0, 5).map((lease) => ({
                            href: `/leases/${lease.id}`,
                            title: lease.code,
                            meta: `${lease.tenant ?? 'No tenant'} · ${lease.asset ?? 'No asset'}`,
                            value: currency(
                                lease.balance_remaining,
                                props.app.locale,
                                lease.currency,
                            ),
                            tone: 'danger',
                        }))}
                    />
                </WorkspacePanel>

                <WorkspacePanel
                    eyebrow="Service"
                    title="Maintenance queue"
                    description="Latest requests across the properties in scope."
                    action={{
                        label: 'Open queue',
                        href: '/maintenance-requests',
                    }}
                >
                    <RecordList
                        empty="No maintenance requests."
                        rows={recentMaintenance.slice(0, 5).map((request) => ({
                            href: `/maintenance-requests/${request.id}`,
                            title: request.title,
                            meta: request.asset?.title_en ?? 'No asset',
                            value: request.status,
                            status: request.status,
                        }))}
                    />
                </WorkspacePanel>
            </div>

            <div className="pmc-command-grid is-three">
                <WorkspacePanel
                    eyebrow="Health"
                    title={`${healthScore}% operating readiness`}
                    description="Setup, occupancy, map, and contract signals."
                >
                    <HealthSignals
                        signals={[
                            {
                                label: 'Setup',
                                value:
                                    setupChecklist.length > 0
                                        ? Math.round(
                                              (completedSetup /
                                                  setupChecklist.length) *
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
                                value: mapSummary?.coverage_percent ?? 0,
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
                    <RecordList
                        empty="No leases are expiring soon."
                        rows={expiringLeases.slice(0, 4).map((lease) => ({
                            href: `/leases/${lease.id}`,
                            title: lease.code,
                            meta: `${lease.tenant ?? 'No tenant'} · ${lease.asset ?? 'No asset'}`,
                            value: `${lease.days_remaining ?? 0} days`,
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
                    <RecordList
                        empty="No payments have been posted."
                        rows={recentPayments.slice(0, 4).map((payment) => ({
                            href: `/payments/${payment.id}`,
                            title:
                                payment.tenant_profile?.user?.name ??
                                `Payment #${payment.id}`,
                            meta: humanDate(
                                payment.received_on,
                                props.app.locale,
                            ),
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
        </AdminLayout>
    );
}

function ActionQueue({ actions }: { actions: NextAction[] }) {
    if (actions.length === 0) {
        return null;
    }

    return (
        <section className="pmc-action-queue" aria-label="Next actions">
            <div className="pmc-action-queue-label">
                <span>Today</span>
                <strong>Next actions</strong>
            </div>
            <div className="pmc-action-queue-grid">
                {actions.map((action, index) => (
                    <Link
                        key={`${action.href}-${action.label}`}
                        href={action.href}
                    >
                        <span>{String(index + 1).padStart(2, '0')}</span>
                        <i className={`bi ${action.icon}`} />
                        <div>
                            <strong>{action.label}</strong>
                            <small>{action.description}</small>
                        </div>
                        <i className="bi bi-arrow-up-right" />
                    </Link>
                ))}
            </div>
        </section>
    );
}

function RecordList({
    rows,
    empty,
}: {
    rows: Array<{
        href: string;
        title: string;
        meta: string;
        value: string;
        status?: string;
        tone?: 'success' | 'warning' | 'danger';
    }>;
    empty: string;
}) {
    if (rows.length === 0) {
        return <div className="pmc-command-empty">{empty}</div>;
    }

    return (
        <div className="pmc-command-list">
            {rows.map((row) => (
                <Link key={`${row.href}-${row.title}`} href={row.href}>
                    <div>
                        <strong>{row.title}</strong>
                        <span>{row.meta}</span>
                    </div>
                    {row.status ? (
                        <StatusBadge value={row.status} />
                    ) : (
                        <em className={`is-${row.tone ?? 'success'}`}>
                            {row.value}
                        </em>
                    )}
                </Link>
            ))}
        </div>
    );
}

function HealthSignals({
    signals,
}: {
    signals: Array<{ label: string; value: number; href: string }>;
}) {
    return (
        <div className="pmc-health-signals">
            {signals.map((signal) => (
                <Link key={signal.label} href={signal.href}>
                    <div>
                        <span>{signal.label}</span>
                        <strong>{signal.value}%</strong>
                    </div>
                    <div className="pmc-health-track">
                        <i
                            style={{
                                width: `${Math.min(100, Math.max(0, signal.value))}%`,
                            }}
                        />
                    </div>
                </Link>
            ))}
        </div>
    );
}
