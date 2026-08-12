import { Link, usePage } from '@inertiajs/react';
import type { CSSProperties, ReactNode } from 'react';

import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate, localizedNumber, percent } from '@/lib/utils';
import { PropertyContextSwitcher } from '@/modules/shell/property-context-switcher';

import type {
    OperationsCurrencyPosition,
    PropertyPerformance,
} from '../operations-types';
import type { OperationsDashboardProps } from '../types';
import { platformMetrics } from './platform-metrics';
import { portfolioMetrics } from './portfolio-metrics';
import { propertyFocusUrl } from './property-focus-url';

type ChartStyle = CSSProperties & { '--bar-height': string };

export function ManagementCommandCenter({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t } = useTranslator();
    const firstName = props.auth.user?.name?.trim().split(/\s+/)[0] ?? '';
    const propertyId = props.propertyFocus.selected?.id;
    const metrics =
        props.mode === 'portfolio'
            ? portfolioMetrics(props, locale, t)
            : platformMetrics(props, locale, t);

    return (
        <div className="pmc-management-dashboard">
            <header className="pmc-management-heading">
                <div>
                    <h1>
                        {t('dashboard.good_morning', 'Good morning, :name', {
                            name: firstName,
                        })}
                        <i className="bi bi-sun" aria-hidden="true" />
                    </h1>
                    <p>{t('dashboard.operations_description')}</p>
                </div>
                <DashboardScopeControls props={props} />
            </header>

            <MetricGrid metrics={metrics} />

            <div className="pmc-dashboard-layout is-top-row">
                <FinancialOverview props={props} />
                <OccupancyOverview props={props} />
                <LeaseSummary props={props} />
                <ActionCenter props={props} />
            </div>

            <div className="pmc-dashboard-layout is-middle-row">
                <RentCollectionOverview props={props} />
                <MaintenanceOverview props={props} />
                <PropertyPerformancePanel props={props} />
                <QuickActions mode={props.mode} propertyId={propertyId} />
            </div>

            <div className="pmc-dashboard-layout is-bottom-row">
                <UpcomingCollections props={props} />
                <RecentActivity props={props} />
                <SystemStatus props={props} />
            </div>
        </div>
    );
}

function DashboardScopeControls({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { t } = useTranslator();
    const { url } = usePage();
    const selected = props.propertyFocus.selected;

    return (
        <div className="pmc-dashboard-scope-controls">
            <nav aria-label={t('dashboard.reporting_period')}>
                {(['month', 'quarter', 'year'] as const).map((period) => (
                    <Link
                        key={period}
                        href={periodHref(period, selected?.id)}
                        className={props.period === period ? 'active' : ''}
                        preserveScroll
                    >
                        {t(`dashboard.period_${period}`)}
                    </Link>
                ))}
            </nav>
            {props.propertyContext ? (
                <PropertyContextSwitcher
                    context={props.propertyContext}
                    currentUrl={url}
                    collapsed={false}
                    onExpand={() => undefined}
                />
            ) : null}
        </div>
    );
}

function FinancialOverview({ props }: { props: OperationsDashboardProps }) {
    const { locale, t } = useTranslator();
    const positions = props.financial.currencyTotals;
    const max = Math.max(
        1,
        ...positions.flatMap((position) => [
            position.revenue,
            position.expenses,
            Math.abs(position.net),
        ]),
    );

    return (
        <DashboardPanel
            className="pmc-financial-overview"
            title={t('dashboard.financial_overview', 'Financial overview')}
            meta={t(`dashboard.period_${props.period}`)}
        >
            <div className="pmc-dashboard-legend" aria-hidden="true">
                <span className="is-income">
                    {t('dashboard.income', 'Income')}
                </span>
                <span className="is-expense">{t('dashboard.expenses')}</span>
                <span className="is-net">
                    {t('dashboard.net_income', 'Net income')}
                </span>
            </div>
            {positions.length > 0 ? (
                <div className="pmc-financial-chart">
                    {positions.map((position) => (
                        <div key={position.currency}>
                            <div className="pmc-financial-bars">
                                <span
                                    className="is-income"
                                    style={barStyle(position.revenue, max)}
                                    title={currency(
                                        position.revenue,
                                        locale,
                                        position.currency,
                                    )}
                                />
                                <span
                                    className="is-expense"
                                    style={barStyle(position.expenses, max)}
                                    title={currency(
                                        position.expenses,
                                        locale,
                                        position.currency,
                                    )}
                                />
                                <span
                                    className="is-net"
                                    style={barStyle(
                                        Math.abs(position.net),
                                        max,
                                    )}
                                    title={currency(
                                        position.net,
                                        locale,
                                        position.currency,
                                    )}
                                />
                            </div>
                            <strong>{position.currency}</strong>
                        </div>
                    ))}
                </div>
            ) : (
                <DashboardEmpty
                    label={t(
                        'dashboard.no_financial_activity',
                        'No financial activity for this period.',
                    )}
                />
            )}
            <div className="pmc-financial-totals">
                <FinancialTotal
                    label={t('dashboard.income', 'Income')}
                    value={moneyPositions(positions, 'revenue', locale)}
                    tone="success"
                />
                <FinancialTotal
                    label={t('dashboard.expenses')}
                    value={moneyPositions(positions, 'expenses', locale)}
                    tone="danger"
                />
                <FinancialTotal
                    label={t('dashboard.net_income', 'Net income')}
                    value={moneyPositions(positions, 'net', locale)}
                    tone="primary"
                />
            </div>
        </DashboardPanel>
    );
}

function FinancialTotal({
    label,
    value,
    tone,
}: {
    label: string;
    value: string;
    tone: 'success' | 'danger' | 'primary';
}) {
    return (
        <div className={`is-${tone}`}>
            <span>{label}</span>
            <strong>{value}</strong>
        </div>
    );
}

function OccupancyOverview({ props }: { props: OperationsDashboardProps }) {
    const { locale, t } = useTranslator();
    const occupied =
        Number(props.charts.occupancy.occupied ?? 0) +
        Number(props.charts.occupancy.partially_occupied ?? 0);
    const vacant = Number(props.charts.occupancy.vacant ?? 0);
    const total = Object.values(props.charts.occupancy).reduce(
        (sum, value) => sum + Number(value),
        0,
    );
    const other = Math.max(0, total - occupied - vacant);
    const occupiedRate = total > 0 ? (occupied / total) * 100 : 0;
    const vacantRate = total > 0 ? (vacant / total) * 100 : 0;
    const donutStyle = {
        '--occupied': `${occupiedRate}%`,
        '--vacant': `${occupiedRate + vacantRate}%`,
    } as CSSProperties;

    return (
        <DashboardPanel
            title={t('dashboard.occupancy_overview', 'Occupancy overview')}
        >
            <div className="pmc-occupancy-summary">
                <div className="pmc-occupancy-donut" style={donutStyle}>
                    <strong>{percent(occupiedRate, locale)}</strong>
                    <span>{t('dashboard.occupied', 'Occupied')}</span>
                </div>
                <dl>
                    <OccupancyValue
                        label={t('dashboard.occupied', 'Occupied')}
                        value={occupied}
                        tone="occupied"
                        locale={locale}
                    />
                    <OccupancyValue
                        label={t('dashboard.vacant', 'Vacant')}
                        value={vacant}
                        tone="vacant"
                        locale={locale}
                    />
                    <OccupancyValue
                        label={t('dashboard.other_units', 'Other')}
                        value={other}
                        tone="other"
                        locale={locale}
                    />
                </dl>
            </div>
            <PanelLink
                href={propertyFocusUrl(
                    '/property-map',
                    props.propertyFocus.selected?.id,
                )}
                label={t('map.title')}
                icon="bi-map"
            />
        </DashboardPanel>
    );
}

function OccupancyValue({
    label,
    value,
    tone,
    locale,
}: {
    label: string;
    value: number;
    tone: string;
    locale: string;
}) {
    return (
        <div className={`is-${tone}`}>
            <dt>{label}</dt>
            <dd>{localizedNumber(value, locale)}</dd>
        </div>
    );
}

function LeaseSummary({ props }: { props: OperationsDashboardProps }) {
    const { locale, t } = useTranslator();
    const expiring30 = props.expiringLeases.filter(
        (lease) => Number(lease.days_remaining ?? 999) <= 30,
    ).length;
    const expiring60 = props.expiringLeases.filter(
        (lease) => Number(lease.days_remaining ?? 999) <= 60,
    ).length;
    const moveOut30 = props.moveOutQueue.items.filter((item) =>
        ['scheduled', 'due_today', 'ready'].includes(item.state),
    ).length;

    return (
        <DashboardPanel
            title={t(
                'dashboard.lease_moveout_summary',
                'Lease & move-out summary',
            )}
        >
            <div className="pmc-summary-list">
                <SummaryRow
                    icon="bi-calendar-event"
                    label={t(
                        'dashboard.expiring_30',
                        'Expiring leases (30 days)',
                    )}
                    value={expiring30}
                    locale={locale}
                    tone="warning"
                />
                <SummaryRow
                    icon="bi-calendar2-week"
                    label={t(
                        'dashboard.expiring_60',
                        'Expiring leases (60 days)',
                    )}
                    value={expiring60}
                    locale={locale}
                    tone="warning"
                />
                <SummaryRow
                    icon="bi-box-arrow-right"
                    label={t('dashboard.moveouts_30', 'Move-outs (30 days)')}
                    value={moveOut30}
                    locale={locale}
                    tone="info"
                />
                <SummaryRow
                    icon="bi-truck"
                    label={t(
                        'dashboard.moveouts_total',
                        'Move-outs requiring attention',
                    )}
                    value={props.moveOutQueue.attention}
                    locale={locale}
                    tone="info"
                />
            </div>
            <PanelLink
                href={propertyFocusUrl(
                    '/lease-renewals?queue=all',
                    props.propertyFocus.selected?.id,
                )}
                label={t('dashboard.open_expiry_report')}
            />
        </DashboardPanel>
    );
}

function ActionCenter({ props }: { props: OperationsDashboardProps }) {
    const { locale, t } = useTranslator();
    const expiring30 = props.expiringLeases.filter(
        (lease) => Number(lease.days_remaining ?? 999) <= 30,
    ).length;
    const rows = [
        {
            icon: 'bi-cash-stack',
            label: t('dashboard.overdue_payments', 'Overdue payments'),
            value: props.arrearsLeases.length,
            tone: 'danger',
        },
        {
            icon: 'bi-calendar-event',
            label: t('dashboard.expiring_30', 'Expiring leases (30 days)'),
            value: expiring30,
            tone: 'warning',
        },
        {
            icon: 'bi-box-arrow-right',
            label: t('dashboard.moveouts_30', 'Move-outs (30 days)'),
            value: props.moveOutQueue.attention,
            tone: 'info',
        },
        {
            icon: 'bi-tools',
            label: t('dashboard.open_maintenance', 'Open maintenance'),
            value: props.stats.openRequests,
            tone: 'danger',
        },
    ];

    return (
        <DashboardPanel
            title={t('nav.action_center')}
            badge={props.nextActions.length}
        >
            <div className="pmc-summary-list is-action-center">
                {rows.map((row) => (
                    <SummaryRow key={row.label} {...row} locale={locale} />
                ))}
            </div>
            <PanelLink href="/action-center" label={t('nav.action_center')} />
        </DashboardPanel>
    );
}

function RentCollectionOverview({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t } = useTranslator();
    const positions = props.financial.currencyTotals;

    return (
        <DashboardPanel
            title={t(
                'dashboard.rent_collection_overview',
                'Rent collection overview',
            )}
            meta={t(`dashboard.period_${props.period}`)}
        >
            <div className="pmc-money-list">
                <MoneyRow
                    label={t('dashboard.collected', 'Collected')}
                    value={moneyPositions(positions, 'scheduledPaid', locale)}
                    tone="success"
                    rate={collectionRate(positions, locale)}
                />
                <MoneyRow
                    label={t('dashboard.pending', 'Pending')}
                    value={moneyDifference(positions, locale)}
                    tone="warning"
                />
                <MoneyRow
                    label={t('dashboard.overdue', 'Overdue')}
                    value={moneyPositions(positions, 'arrears', locale)}
                    tone="danger"
                />
            </div>
            <PanelLink
                href={propertyFocusUrl(
                    '/rent-collection',
                    props.propertyFocus.selected?.id,
                )}
                label={t('dashboard.open_collections')}
            />
        </DashboardPanel>
    );
}

function MaintenanceOverview({ props }: { props: OperationsDashboardProps }) {
    const { locale, t } = useTranslator();
    const recentOpen = props.recentMaintenance.filter(
        (item) => item.status === 'open',
    ).length;
    const inProgress = props.recentMaintenance.filter(
        (item) => item.status === 'in_progress',
    ).length;
    const urgent = props.recentMaintenance.filter((item) =>
        ['urgent', 'high'].includes(item.priority ?? ''),
    ).length;

    return (
        <DashboardPanel
            title={t('dashboard.maintenance_overview', 'Maintenance overview')}
        >
            <div className="pmc-maintenance-metrics">
                <MiniMetric
                    value={urgent}
                    label={t('dashboard.urgent_visible', 'Urgent')}
                    locale={locale}
                    tone="danger"
                />
                <MiniMetric
                    value={inProgress}
                    label={t('dashboard.in_progress_visible', 'In progress')}
                    locale={locale}
                    tone="warning"
                />
                <MiniMetric
                    value={recentOpen}
                    label={t('dashboard.recent_open', 'Recent open')}
                    locale={locale}
                    tone="info"
                />
                <MiniMetric
                    value={props.stats.openRequests}
                    label={t('dashboard.open_total', 'Open total')}
                    locale={locale}
                    tone="success"
                />
            </div>
            <PanelLink
                href={propertyFocusUrl(
                    '/maintenance-requests',
                    props.propertyFocus.selected?.id,
                )}
                label={t('dashboard.open_queue')}
            />
        </DashboardPanel>
    );
}

function PropertyPerformancePanel({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t } = useTranslator();

    return (
        <DashboardPanel
            title={t('dashboard.property_performance')}
            meta={t('dashboard.top_properties', 'Top 5 by attention')}
        >
            <div className="pmc-property-performance-table">
                <div className="pmc-property-performance-head">
                    <span>{t('dashboard.properties')}</span>
                    <span>{t('dashboard.occupancy_rate')}</span>
                    <span>{t('dashboard.net_cash_flow')}</span>
                </div>
                {props.propertyPerformance.slice(0, 5).map((property) => (
                    <PropertyPerformanceRow
                        key={property.id}
                        property={property}
                        locale={locale}
                    />
                ))}
                {props.propertyPerformance.length === 0 ? (
                    <DashboardEmpty
                        label={t(
                            'dashboard.no_property_performance',
                            'No property performance data yet.',
                        )}
                    />
                ) : null}
            </div>
            <PanelLink
                href="/reports"
                label={t('dashboard.open_all_properties')}
            />
        </DashboardPanel>
    );
}

function PropertyPerformanceRow({
    property,
    locale,
}: {
    property: PropertyPerformance;
    locale: string;
}) {
    const title =
        locale === 'ar'
            ? property.title_ar || property.title_en
            : property.title_en || property.title_ar;

    return (
        <Link href={`/property-explorer?property_id=${property.id}`}>
            <strong>{title}</strong>
            <span className="pmc-performance-progress">
                <i
                    style={{
                        width: `${Math.max(0, Math.min(100, property.occupancy_rate))}%`,
                    }}
                />
                {percent(property.occupancy_rate, locale)}
            </span>
            <span>
                {property.currency_totals
                    .map((position) =>
                        currency(position.net, locale, position.currency),
                    )
                    .join(' · ') || '—'}
            </span>
        </Link>
    );
}

function QuickActions({
    mode,
    propertyId,
}: {
    mode: OperationsDashboardProps['mode'];
    propertyId?: number;
}) {
    const { t } = useTranslator();
    const actions =
        mode === 'superadmin'
            ? [
                  {
                      label: t('dashboard.create_portfolio'),
                      href: '/portfolios/create',
                      icon: 'bi-briefcase',
                  },
                  {
                      label: t('dashboard.create_user'),
                      href: '/users/create',
                      icon: 'bi-person-plus',
                  },
                  {
                      label: t('quick_actions.property'),
                      href: '/assets/create',
                      icon: 'bi-house-add',
                  },
                  {
                      label: t('dashboard.create_lease'),
                      href: '/leases/create',
                      icon: 'bi-file-earmark-plus',
                  },
                  {
                      label: t('dashboard.post_payment'),
                      href: '/payments/create',
                      icon: 'bi-wallet2',
                  },
                  {
                      label: t('dashboard.new_maintenance'),
                      href: '/maintenance-requests/create',
                      icon: 'bi-tools',
                  },
              ]
            : [
                  {
                      label: t('quick_actions.property'),
                      href: propertyFocusUrl('/assets/create', propertyId),
                      icon: 'bi-house-add',
                  },
                  {
                      label: t('dashboard.start_tenancy'),
                      href: '/tenants/create?next=lease',
                      icon: 'bi-person-plus',
                  },
                  {
                      label: t('dashboard.create_lease'),
                      href: '/leases/create',
                      icon: 'bi-file-earmark-plus',
                  },
                  {
                      label: t('dashboard.post_payment'),
                      href: '/payments/create',
                      icon: 'bi-wallet2',
                  },
                  {
                      label: t('expenses.create_title'),
                      href: '/expenses/create',
                      icon: 'bi-receipt',
                  },
                  {
                      label: t('dashboard.new_maintenance'),
                      href: '/maintenance-requests/create',
                      icon: 'bi-tools',
                  },
              ];

    return (
        <DashboardPanel title={t('dashboard.quick_actions', 'Quick actions')}>
            <div className="pmc-dashboard-quick-actions">
                {actions.map((action) => (
                    <Link key={action.href} href={action.href}>
                        <i className={`bi ${action.icon}`} aria-hidden="true" />
                        <span>{action.label}</span>
                        <i className="bi bi-chevron-right" aria-hidden="true" />
                    </Link>
                ))}
            </div>
        </DashboardPanel>
    );
}

function UpcomingCollections({ props }: { props: OperationsDashboardProps }) {
    const { locale, t, text } = useTranslator();

    return (
        <DashboardPanel
            title={t('dashboard.upcoming_collections', 'Upcoming collections')}
            meta={t('dashboard.next_seven_days', 'Next 7 days')}
        >
            <div className="pmc-dashboard-table">
                <div className="pmc-dashboard-table-head">
                    <span>{t('tenant_portal.due_date')}</span>
                    <span>{t('tenants.tenant')}</span>
                    <span>{t('assets.property')}</span>
                    <span>{t('tenant_portal.amount')}</span>
                    <span>{t('tenant_portal.status')}</span>
                </div>
                {props.collectionQueue.slice(0, 5).map((item) => (
                    <Link
                        key={item.id}
                        href={`/rent-collection/${item.id}/follow-up`}
                    >
                        <span>
                            {humanDate(item.due_date, props.app.locale)}
                        </span>
                        <strong>{item.tenant ?? text('No tenant')}</strong>
                        <span>
                            {locale === 'ar'
                                ? item.asset_ar || item.asset_en
                                : item.asset_en || item.asset_ar}
                        </span>
                        <span>
                            {currency(
                                item.outstanding_amount,
                                locale,
                                item.currency,
                            )}
                        </span>
                        <em
                            className={
                                item.days_overdue > 0
                                    ? 'is-danger'
                                    : 'is-warning'
                            }
                        >
                            {item.days_overdue > 0
                                ? t('dashboard.overdue', 'Overdue')
                                : t('dashboard.pending', 'Pending')}
                        </em>
                    </Link>
                ))}
                {props.collectionQueue.length === 0 ? (
                    <DashboardEmpty label={t('dashboard.no_collection_work')} />
                ) : null}
            </div>
            <PanelLink
                href={propertyFocusUrl(
                    '/rent-collection',
                    props.propertyFocus.selected?.id,
                )}
                label={t('dashboard.open_collections')}
            />
        </DashboardPanel>
    );
}

function RecentActivity({ props }: { props: OperationsDashboardProps }) {
    const { locale, t } = useTranslator();
    const activity = [
        ...props.recentPayments.map((payment) => ({
            key: `payment-${payment.id}`,
            href: `/payments/${payment.id}`,
            icon: 'bi-wallet2',
            tone: 'success',
            title: t('dashboard.payment_received', 'Payment received'),
            detail:
                payment.tenant_profile?.user?.name ??
                t('payments.payment_number', undefined, { id: payment.id }),
            value: currency(payment.amount, locale, payment.currency),
            date: payment.received_on,
        })),
        ...props.recentMaintenance.map((request) => ({
            key: `maintenance-${request.id}`,
            href: `/maintenance-requests/${request.id}`,
            icon: 'bi-tools',
            tone: 'warning',
            title: request.title,
            detail:
                locale === 'ar'
                    ? request.asset?.title_ar || request.asset?.title_en
                    : request.asset?.title_en || request.asset?.title_ar,
            value: t(`status.${request.status}`, request.status),
            date: request.created_at,
        })),
    ]
        .sort(
            (left, right) =>
                Date.parse(right.date ?? '') - Date.parse(left.date ?? ''),
        )
        .slice(0, 5);

    return (
        <DashboardPanel
            title={t('dashboard.recent_activity', 'Recent activity')}
        >
            <div className="pmc-dashboard-activity-list">
                {activity.map((item) => (
                    <Link key={item.key} href={item.href}>
                        <i
                            className={`bi ${item.icon} is-${item.tone}`}
                            aria-hidden="true"
                        />
                        <span>
                            <strong>{item.title}</strong>
                            <small>{item.detail}</small>
                        </span>
                        <span>
                            <strong>{item.value}</strong>
                            <small>
                                {humanDate(item.date, props.app.locale)}
                            </small>
                        </span>
                    </Link>
                ))}
                {activity.length === 0 ? (
                    <DashboardEmpty
                        label={t(
                            'dashboard.no_recent_activity',
                            'No recent activity.',
                        )}
                    />
                ) : null}
            </div>
            <PanelLink href="/audit-logs" label={t('actions.view_all')} />
        </DashboardPanel>
    );
}

function SystemStatus({ props }: { props: OperationsDashboardProps }) {
    const { t } = useTranslator();
    const readiness = props.readinessStatus;
    const rows = [
        {
            label: t('dashboard.application', 'Application'),
            value: t('dashboard.operational', 'Operational'),
            ok: true,
        },
        {
            label: t('dashboard.launch_gate', 'Launch gate'),
            value: readiness
                ? t(`status.${readiness.status}`, readiness.status)
                : t('dashboard.not_available', 'Not available'),
            ok: readiness?.status === 'ready',
        },
        {
            label: t('dashboard.automatic_blockers'),
            value: String(readiness?.automatic_blocked ?? 0),
            ok: (readiness?.automatic_blocked ?? 0) === 0,
        },
        {
            label: t('dashboard.evidence_remaining'),
            value: String(readiness?.evidence_remaining ?? 0),
            ok: (readiness?.evidence_remaining ?? 0) === 0,
        },
        {
            label: t('cms.website_control'),
            value: props.cmsStatus
                ? `${props.cmsStatus.published} ${t('status.published')}`
                : t('dashboard.not_available', 'Not available'),
            ok: (props.cmsStatus?.published ?? 0) > 0,
        },
    ];

    return (
        <DashboardPanel title={t('dashboard.system_status', 'System status')}>
            <div className="pmc-system-status-list">
                {rows.map((row) => (
                    <div key={row.label}>
                        <i
                            className={`bi ${row.ok ? 'bi-check-circle' : 'bi-exclamation-circle'}`}
                            aria-hidden="true"
                        />
                        <span>{row.label}</span>
                        <strong
                            className={row.ok ? 'is-success' : 'is-warning'}
                        >
                            {row.value}
                        </strong>
                    </div>
                ))}
            </div>
            <PanelLink
                href="/system/readiness"
                label={t('dashboard.launch_control_title')}
            />
        </DashboardPanel>
    );
}

function DashboardPanel({
    title,
    meta,
    badge,
    className = '',
    children,
}: {
    title: string;
    meta?: string;
    badge?: number;
    className?: string;
    children: ReactNode;
}) {
    const { locale } = useTranslator();

    return (
        <section className={`pmc-dashboard-panel ${className}`}>
            <header>
                <h2>{title}</h2>
                {badge !== undefined ? (
                    <strong className="pmc-panel-badge">
                        {localizedNumber(badge, locale)}
                    </strong>
                ) : null}
                {meta ? <span>{meta}</span> : null}
            </header>
            <div className="pmc-dashboard-panel-body">{children}</div>
        </section>
    );
}

function SummaryRow({
    icon,
    label,
    value,
    locale,
    tone,
}: {
    icon: string;
    label: string;
    value: number;
    locale: string;
    tone: string;
}) {
    return (
        <div>
            <i className={`bi ${icon} is-${tone}`} aria-hidden="true" />
            <span>{label}</span>
            <strong className={`is-${tone}`}>
                {localizedNumber(value, locale)}
            </strong>
        </div>
    );
}

function MoneyRow({
    label,
    value,
    tone,
    rate,
}: {
    label: string;
    value: string;
    tone: string;
    rate?: string;
}) {
    return (
        <div>
            <i className={`is-${tone}`} aria-hidden="true" />
            <span>{label}</span>
            <strong>{value}</strong>
            {rate ? <em className={`is-${tone}`}>{rate}</em> : null}
        </div>
    );
}

function MiniMetric({
    value,
    label,
    locale,
    tone,
}: {
    value: number;
    label: string;
    locale: string;
    tone: string;
}) {
    return (
        <div className={`is-${tone}`}>
            <strong>{localizedNumber(value, locale)}</strong>
            <span>{label}</span>
        </div>
    );
}

function PanelLink({
    href,
    label,
    icon,
}: {
    href: string;
    label: string;
    icon?: string;
}) {
    return (
        <Link className="pmc-dashboard-panel-link" href={href}>
            {label}
            <i
                className={`bi ${icon ?? 'bi-arrow-up-right'}`}
                aria-hidden="true"
            />
        </Link>
    );
}

function DashboardEmpty({ label }: { label: string }) {
    return <p className="pmc-dashboard-empty">{label}</p>;
}

function barStyle(value: number, max: number): ChartStyle {
    return {
        '--bar-height': `${Math.max(8, Math.round((value / max) * 100))}%`,
    };
}

function moneyPositions(
    positions: OperationsCurrencyPosition[],
    field: 'scheduledPaid' | 'revenue' | 'expenses' | 'net' | 'arrears',
    locale: string,
): string {
    if (positions.length === 0) {
        return '—';
    }

    return positions
        .map((position) => currency(position[field], locale, position.currency))
        .join(' · ');
}

function moneyDifference(
    positions: OperationsCurrencyPosition[],
    locale: string,
): string {
    if (positions.length === 0) {
        return '—';
    }

    return positions
        .map((position) =>
            currency(
                Math.max(0, position.scheduledDue - position.scheduledPaid),
                locale,
                position.currency,
            ),
        )
        .join(' · ');
}

function collectionRate(
    positions: OperationsCurrencyPosition[],
    locale: string,
): string | undefined {
    return positions.length === 1
        ? percent(positions[0].collectionRate, locale)
        : undefined;
}

function periodHref(period: string, propertyId?: number): string {
    const query = new URLSearchParams({ period });

    if (propertyId) {
        query.set('property_id', String(propertyId));
    }

    return `/dashboard?${query.toString()}`;
}
