import { Head, Link } from '@inertiajs/react';

import {
    MetricGrid,
    StatusBadge,
    WorkspaceHeader,
    WorkspacePanel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency, currency, humanDate } from '@/lib/utils';

import { operationsHealthScore } from '../metrics';
import type { DashboardPageProps, NextAction } from '../types';

export function OperationsDashboard({ props }: { props: DashboardPageProps }) {
    const { locale, t, text } = useTranslator();
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
    const occupiedAssets =
        Number(occupancy.occupied ?? 0) +
        Number(occupancy.partially_occupied ?? 0);
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
            <Head title={text('Dashboard')} />

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
                                ? t('dashboard.portfolios_users', undefined, {
                                      portfolios:
                                          props.stats.totalPortfolios ?? 0,
                                      users: props.stats.totalUsers ?? 0,
                                  })
                                : t('dashboard.vacant_units', undefined, {
                                      count: props.stats.vacantUnits ?? 0,
                                  }),
                        icon: 'bi-buildings',
                        tone: 'ink',
                        href: '/assets',
                    },
                    {
                        label: 'Portfolio value',
                        value: compactCurrency(
                            Number(props.stats.totalValue ?? 0),
                            props.app.locale,
                        ),
                        detail: t('dashboard.active_leases_count', undefined, {
                            count: props.stats.activeLeases ?? 0,
                        }),
                        icon: 'bi-bank',
                        tone: 'blue',
                        href: '/assets',
                    },
                    {
                        label: 'Collected this month',
                        value: compactCurrency(
                            Number(props.stats.monthlyRevenue ?? 0),
                            props.app.locale,
                        ),
                        detail: t('dashboard.expenses_amount', undefined, {
                            amount: currency(
                                Number(props.stats.monthlyExpenses ?? 0),
                                locale,
                            ),
                        }),
                        icon: 'bi-cash-stack',
                        tone: 'teal',
                        href: '/payments',
                    },
                    {
                        label: 'Outstanding rent',
                        value: compactCurrency(
                            Number(props.stats.arrears ?? 0),
                            props.app.locale,
                        ),
                        detail: t('dashboard.open_service_count', undefined, {
                            count: props.stats.openRequests ?? 0,
                        }),
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
                            meta: `${lease.tenant ?? text('No tenant')} · ${lease.asset ?? text('No asset')}`,
                            value: currency(
                                lease.arrears_amount ?? 0,
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
                            meta:
                                (locale === 'ar'
                                    ? request.asset?.title_ar ||
                                      request.asset?.title_en
                                    : request.asset?.title_en ||
                                      request.asset?.title_ar) ??
                                text('No asset'),
                            value: request.status,
                            status: request.status,
                        }))}
                    />
                </WorkspacePanel>
            </div>

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
                    <RecordList
                        empty="No payments have been posted."
                        rows={recentPayments.slice(0, 4).map((payment) => ({
                            href: `/payments/${payment.id}`,
                            title:
                                payment.tenant_profile?.user?.name ??
                                t('payments.payment_number', undefined, {
                                    id: payment.id,
                                }),
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
    const { t, text } = useTranslator();

    if (actions.length === 0) {
        return null;
    }

    return (
        <section className="pmc-action-queue" aria-label={text('Next actions')}>
            <div className="pmc-action-queue-label">
                <span>{text('Today')}</span>
                <strong>{text('Next actions')}</strong>
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
                            <strong>{text(action.label)}</strong>
                            <small>{actionDescription(action, t, text)}</small>
                        </div>
                        <i className="bi bi-arrow-up-right" />
                    </Link>
                ))}
            </div>
        </section>
    );
}

function actionDescription(
    action: NextAction,
    t: ReturnType<typeof useTranslator>['t'],
    translate: (value: string) => string,
): string {
    if (action.href !== '/property-map') {
        return translate(action.description);
    }

    const [positions = '0', identities = '0'] =
        action.description.match(/\d+/g) ?? [];

    return t('dashboard.map_action_description', undefined, {
        positions,
        identities,
    });
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
    const { text } = useTranslator();

    if (rows.length === 0) {
        return <div className="pmc-command-empty">{text(empty)}</div>;
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
    const { text } = useTranslator();

    return (
        <div className="pmc-health-signals">
            {signals.map((signal) => (
                <Link key={signal.label} href={signal.href}>
                    <div>
                        <span>{text(signal.label)}</span>
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
