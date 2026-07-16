import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { Bar, Doughnut } from 'react-chartjs-2';

import { StatCard } from '@/components/stat-card';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency, humanDate, percent } from '@/lib/utils';
import type { SharedProps, TableFilters } from '@/types';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    ArcElement,
    Tooltip,
    Legend,
);

type ArrearsLease = {
    id: number;
    code: string;
    tenant?: string | null;
    asset?: string | null;
    ends_at?: string | null;
    balance_remaining: number;
    currency: string;
};

type TopAsset = {
    asset: string;
    revenue: number;
    currency: string;
    lease_count: number;
};

type PaymentRow = {
    id: number;
    reference: string;
    tenant?: string | null;
    lease?: string | null;
    amount: number;
    currency: string;
    received_on?: string | null;
};

type ExpenseRow = {
    id: number;
    title: string;
    category: string;
    asset?: string | null;
    amount: number;
    currency: string;
    incurred_on?: string | null;
};

type MaintenanceRow = {
    id: number;
    title: string;
    asset?: string | null;
    tenant?: string | null;
    status: string;
    priority: string;
    created_at?: string | null;
};

type ReportPreset = {
    id: number;
    title_en: string;
    title_ar?: string | null;
    visibility: string;
    is_default: boolean;
    filters: TableFilters;
    url: string;
};

type PageProps = SharedProps & {
    mode: 'portfolio' | 'superadmin';
    filters: TableFilters;
    portfolioOptions: Array<{ id: number; name: string }>;
    summary: {
        revenue: number;
        expenses: number;
        net: number;
        occupancyRate: number;
        arrears: number;
        activeLeases: number;
        unpaidLeases: number;
        openRequests: number;
        resolvedRequests: number;
    };
    charts: {
        revenueByMonth: Record<string, number>;
        expenseByCategory: Record<string, number>;
        assetMix: Record<string, number>;
        maintenanceByStatus: Record<string, number>;
    };
    arrearsLeases: ArrearsLease[];
    topAssets: TopAsset[];
    recentPayments: PaymentRow[];
    recentExpenses: ExpenseRow[];
    maintenanceBacklog: MaintenanceRow[];
    savedPresets: ReportPreset[];
};

export default function ReportsPage() {
    const { props } = usePage<PageProps>();
    const isSuperadmin = props.auth.user?.roles.includes('superadmin') ?? false;
    const [filters, setFilters] = useState({
        date_from: String(props.filters.date_from ?? ''),
        date_to: String(props.filters.date_to ?? ''),
        portfolio_id: props.filters.portfolio_id
            ? String(props.filters.portfolio_id)
            : 'all',
    });
    const [presetTitle, setPresetTitle] = useState('');

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get('/reports', cleanFilters(filters), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const exportHref = `/reports/export?${new URLSearchParams(
        cleanFilters(filters),
    ).toString()}`;
    const revenueSource = props.charts.revenueByMonth;
    const expenseSource = props.charts.expenseByCategory;
    const assetMixSource = props.charts.assetMix;
    const maintenanceSource = props.charts.maintenanceByStatus;
    const reportRange =
        filters.date_from || filters.date_to
            ? `${filters.date_from || 'Start'} -> ${filters.date_to || 'Today'}`
            : 'All dates';
    const collectionBase = props.summary.revenue + props.summary.arrears;
    const collectionRate =
        collectionBase > 0
            ? (props.summary.revenue / collectionBase) * 100
            : props.summary.revenue > 0
              ? 100
              : 0;
    const expenseRatio =
        props.summary.revenue > 0
            ? (props.summary.expenses / props.summary.revenue) * 100
            : 0;
    const reportActions = buildReportActions(props.summary);
    const savePreset = () => {
        const title = presetTitle.trim();

        if (!title) {
            return;
        }

        router.post(
            '/reports/presets',
            {
                resource: 'portfolio-report',
                title_en: title,
                title_ar: '',
                visibility: 'private',
                is_default: false,
                filters_json: cleanFilters(filters),
            },
            { preserveScroll: true },
        );
        setPresetTitle('');
    };

    return (
        <AdminLayout>
            <Head title="Reports" />

            <section className="pmc-report-command-center mb-4">
                <div>
                    <div className="pmc-kicker mb-3">Reports control room</div>
                    <h1>
                        Know what was collected, what is leaking, and what needs
                        action.
                    </h1>
                    <p>
                        This report ties rent, expenses, arrears, assets, and
                        maintenance into one operating view. It is scoped to the
                        portfolio permissions of the signed-in user.
                    </p>
                    <div className="pmc-report-context">
                        <span>
                            <i className="bi bi-calendar3" />
                            {reportRange}
                        </span>
                        <span>
                            <i className="bi bi-shield-check" />
                            {props.mode === 'superadmin'
                                ? 'Platform-wide scope'
                                : 'Portfolio scope'}
                        </span>
                        <span>
                            <i className="bi bi-download" />
                            Excel-ready
                        </span>
                    </div>
                </div>

                <div className="pmc-report-health-card">
                    <div className="pmc-report-meter">
                        <span>{percent(collectionRate)}</span>
                        <small>Collection health</small>
                    </div>
                    <div className="pmc-report-health-list">
                        <ReportSignal
                            label="Collected"
                            value={currency(
                                props.summary.revenue,
                                props.app.locale,
                            )}
                            tone="good"
                        />
                        <ReportSignal
                            label="Still owed"
                            value={currency(
                                props.summary.arrears,
                                props.app.locale,
                            )}
                            tone={props.summary.arrears > 0 ? 'risk' : 'good'}
                        />
                        <ReportSignal
                            label="Expense drag"
                            value={percent(expenseRatio)}
                            tone={expenseRatio > 35 ? 'risk' : 'neutral'}
                        />
                    </div>
                    <div className="d-grid gap-2 mt-3">
                        <a href={exportHref} className="btn btn-primary">
                            <i className="bi bi-download me-2" />
                            Export .xlsx report
                        </a>
                        <Link
                            href="/payments"
                            className="btn btn-outline-secondary"
                        >
                            <i className="bi bi-cash-stack me-2" />
                            Post payment
                        </Link>
                    </div>
                </div>
            </section>

            <form
                className="pmc-report-filter-card p-3 p-lg-4 mb-4"
                onSubmit={applyFilters}
            >
                <div className="pmc-report-filter-head">
                    <div>
                        <div className="pmc-kicker mb-2">Report filters</div>
                        <h2 className="h5 mb-1">Change the operating window</h2>
                        <p className="mb-0 text-secondary">
                            Filters update the cards, charts, tables, and Excel
                            export using the same permission scope.
                        </p>
                    </div>
                    <Link href="/documentation" className="btn btn-light">
                        <i className="bi bi-question-circle me-2" />
                        Report guide
                    </Link>
                </div>
                <div className="row g-3 align-items-end">
                    <div className="col-md-3">
                        <label className="form-label pmc-form-label">
                            Date from
                        </label>
                        <input
                            type="date"
                            className="form-control"
                            value={filters.date_from}
                            onChange={(event) =>
                                setFilters((current) => ({
                                    ...current,
                                    date_from: event.currentTarget.value,
                                }))
                            }
                        />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label pmc-form-label">
                            Date to
                        </label>
                        <input
                            type="date"
                            className="form-control"
                            value={filters.date_to}
                            onChange={(event) =>
                                setFilters((current) => ({
                                    ...current,
                                    date_to: event.currentTarget.value,
                                }))
                            }
                        />
                    </div>
                    {isSuperadmin ? (
                        <div className="col-md-3">
                            <label className="form-label pmc-form-label">
                                Portfolio
                            </label>
                            <select
                                className="form-select"
                                value={filters.portfolio_id}
                                onChange={(event) =>
                                    setFilters((current) => ({
                                        ...current,
                                        portfolio_id: event.currentTarget.value,
                                    }))
                                }
                            >
                                <option value="all">All portfolios</option>
                                {props.portfolioOptions.map((portfolio) => (
                                    <option
                                        key={portfolio.id}
                                        value={portfolio.id}
                                    >
                                        {portfolio.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    ) : null}
                    <div className="col-md-3 d-flex gap-2">
                        <button className="btn btn-primary flex-fill">
                            <i className="bi bi-funnel me-2" />
                            Apply
                        </button>
                        <Link
                            href="/reports"
                            className="btn btn-outline-secondary"
                        >
                            Reset
                        </Link>
                    </div>
                </div>
            </form>

            <section className="pmc-report-presets mb-4">
                <div className="pmc-report-presets-head">
                    <div>
                        <div className="pmc-kicker mb-2">Saved shortcuts</div>
                        <h2>Report presets</h2>
                        <p>
                            Save the current filters for arrears, expiry,
                            occupancy, maintenance, and net revenue reviews.
                        </p>
                    </div>
                    <div className="pmc-report-preset-form">
                        <input
                            className="form-control"
                            value={presetTitle}
                            placeholder="Preset name"
                            onChange={(event) =>
                                setPresetTitle(event.currentTarget.value)
                            }
                        />
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={savePreset}
                        >
                            Save preset
                        </button>
                    </div>
                </div>
                <div className="pmc-report-preset-grid">
                    {props.savedPresets.length > 0 ? (
                        props.savedPresets.map((preset) => (
                            <article
                                key={preset.id}
                                className="pmc-report-preset-card"
                            >
                                <div>
                                    <strong>{preset.title_en}</strong>
                                    <span>{preset.visibility}</span>
                                </div>
                                <div className="pmc-report-preset-actions">
                                    <Link
                                        href={preset.url}
                                        className="btn btn-light btn-sm"
                                    >
                                        Open
                                    </Link>
                                    <button
                                        type="button"
                                        className="btn btn-outline-danger btn-sm"
                                        onClick={() =>
                                            router.delete(
                                                `/reports/presets/${preset.id}`,
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        Remove
                                    </button>
                                </div>
                            </article>
                        ))
                    ) : (
                        <p className="pmc-empty-inline">
                            No saved presets yet. Choose filters and save the
                            view.
                        </p>
                    )}
                </div>
            </section>

            <div className="pmc-report-decision-grid mb-4">
                <DecisionCard
                    icon="bi-wallet2"
                    label="Cash collected"
                    value={currency(props.summary.revenue, props.app.locale)}
                    detail={`${props.recentPayments.length} recent posted payments in view`}
                    href="/payments"
                />
                <DecisionCard
                    icon="bi-exclamation-octagon"
                    label="Collection gap"
                    value={currency(props.summary.arrears, props.app.locale)}
                    detail={`${props.summary.unpaidLeases} leases have unpaid balances`}
                    href="/leases?status=active"
                    tone={props.summary.arrears > 0 ? 'risk' : 'good'}
                />
                <DecisionCard
                    icon="bi-tools"
                    label="Service pressure"
                    value={props.summary.openRequests}
                    detail={`${props.summary.resolvedRequests} resolved requests in the selected range`}
                    href="/maintenance-requests"
                    tone={props.summary.openRequests > 0 ? 'warn' : 'good'}
                />
                <DecisionCard
                    icon="bi-building-check"
                    label="Occupancy"
                    value={percent(props.summary.occupancyRate)}
                    detail={`${props.summary.activeLeases} active leases currently counted`}
                    href="/assets"
                />
            </div>

            <div className="row g-3 mb-4">
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Revenue"
                        value={currency(
                            props.summary.revenue,
                            props.app.locale,
                        )}
                        hint={`${props.recentPayments.length} recent payments`}
                        tone="accent"
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Expenses"
                        value={currency(
                            props.summary.expenses,
                            props.app.locale,
                        )}
                        hint={`${props.recentExpenses.length} recent costs`}
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Net position"
                        value={currency(props.summary.net, props.app.locale)}
                        hint={`${percent(props.summary.occupancyRate)} occupancy`}
                        tone="teal"
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Arrears"
                        value={currency(
                            props.summary.arrears,
                            props.app.locale,
                        )}
                        hint={`${props.summary.unpaidLeases} unpaid leases`}
                    />
                </div>
            </div>

            <div className="pmc-report-strip mb-4">
                <ReportMiniStat
                    icon="bi-file-earmark-text"
                    label="Active leases"
                    value={props.summary.activeLeases}
                />
                <ReportMiniStat
                    icon="bi-tools"
                    label="Open requests"
                    value={props.summary.openRequests}
                />
                <ReportMiniStat
                    icon="bi-check-circle"
                    label="Resolved requests"
                    value={props.summary.resolvedRequests}
                />
                <ReportMiniStat
                    icon="bi-buildings"
                    label="Top revenue assets"
                    value={props.topAssets.length}
                />
            </div>

            <div className="pmc-report-action-panel mb-4">
                <div>
                    <div className="pmc-kicker mb-2">What to do next</div>
                    <h2>Turn the report into work, not wallpaper.</h2>
                    <p>
                        The system reads the selected range and highlights the
                        next operational moves. If this list is quiet, your
                        portfolio is in good shape for this window.
                    </p>
                </div>
                <div className="pmc-report-action-list">
                    {reportActions.map((action) => (
                        <Link
                            key={action.title}
                            href={action.href}
                            className={`pmc-report-action ${action.tone}`}
                        >
                            <span>
                                <i className={`bi ${action.icon}`} />
                            </span>
                            <div>
                                <strong>{action.title}</strong>
                                <small>{action.detail}</small>
                            </div>
                            <i className="bi bi-arrow-right-short" />
                        </Link>
                    ))}
                </div>
            </div>

            <div className="pmc-report-section-grid">
                <div className="pmc-report-section-card is-wide">
                    <ChartCard
                        title="Revenue by month"
                        empty={Object.keys(revenueSource).length === 0}
                    >
                        <Bar
                            data={{
                                labels: Object.keys(revenueSource),
                                datasets: [
                                    {
                                        label: 'Revenue',
                                        data: Object.values(revenueSource),
                                        backgroundColor: '#ef6c2f',
                                        borderRadius: 12,
                                    },
                                ],
                            }}
                        />
                    </ChartCard>
                </div>

                <div className="pmc-report-section-card">
                    <ChartCard
                        title="Asset mix"
                        empty={Object.keys(assetMixSource).length === 0}
                    >
                        <Doughnut
                            data={{
                                labels: Object.keys(assetMixSource),
                                datasets: [
                                    {
                                        data: Object.values(assetMixSource),
                                        backgroundColor: [
                                            '#ef6c2f',
                                            '#0c8a7c',
                                            '#24314a',
                                            '#ffca4b',
                                            '#2563eb',
                                        ],
                                    },
                                ],
                            }}
                        />
                    </ChartCard>
                </div>

                <div className="pmc-report-section-card">
                    <ChartCard
                        title="Expense categories"
                        empty={Object.keys(expenseSource).length === 0}
                    >
                        <Bar
                            data={{
                                labels: Object.keys(expenseSource),
                                datasets: [
                                    {
                                        label: 'Expenses',
                                        data: Object.values(expenseSource),
                                        backgroundColor: '#0c8a7c',
                                        borderRadius: 12,
                                    },
                                ],
                            }}
                        />
                    </ChartCard>
                </div>

                <div className="pmc-report-section-card">
                    <ChartCard
                        title="Maintenance status"
                        empty={Object.keys(maintenanceSource).length === 0}
                    >
                        <Doughnut
                            data={{
                                labels: Object.keys(maintenanceSource),
                                datasets: [
                                    {
                                        data: Object.values(maintenanceSource),
                                        backgroundColor: [
                                            '#ef6c2f',
                                            '#ffca4b',
                                            '#0c8a7c',
                                            '#64748b',
                                        ],
                                    },
                                ],
                            }}
                        />
                    </ChartCard>
                </div>

                <div className="pmc-report-section-card is-wide">
                    <ReportTable
                        title="Arrears watchlist"
                        emptyText="No leases with outstanding balances in this scope."
                        headers={['Lease', 'Tenant / asset', 'End', 'Balance']}
                        rows={props.arrearsLeases.map((lease) => [
                            <Link
                                key="lease"
                                href={`/leases?search=${encodeURIComponent(lease.code)}`}
                                className="fw-semibold"
                            >
                                {lease.code}
                            </Link>,
                            <>
                                <div>{lease.tenant ?? 'No tenant'}</div>
                                <div className="small text-secondary">
                                    {lease.asset ?? 'No asset'}
                                </div>
                            </>,
                            humanDate(lease.ends_at, props.app.locale),
                            currency(
                                lease.balance_remaining,
                                props.app.locale,
                                lease.currency,
                            ),
                        ])}
                    />
                </div>

                <div className="pmc-report-section-card">
                    <ReportTable
                        title="Top revenue assets"
                        emptyText="No asset-linked revenue in this report range."
                        headers={['Asset', 'Leases', 'Revenue']}
                        rows={props.topAssets.map((asset) => [
                            asset.asset,
                            asset.lease_count,
                            currency(
                                asset.revenue,
                                props.app.locale,
                                asset.currency,
                            ),
                        ])}
                    />
                </div>

                <div className="pmc-report-section-card">
                    <ReportTable
                        title="Recent payments"
                        emptyText="No posted payments in this report range."
                        headers={['Reference', 'Tenant', 'Date', 'Amount']}
                        rows={props.recentPayments.map((payment) => [
                            <Link
                                key="payment"
                                href={`/payments?search=${encodeURIComponent(payment.reference)}`}
                                className="fw-semibold"
                            >
                                {payment.reference}
                            </Link>,
                            payment.tenant ?? payment.lease ?? '-',
                            humanDate(payment.received_on, props.app.locale),
                            currency(
                                payment.amount,
                                props.app.locale,
                                payment.currency,
                            ),
                        ])}
                    />
                </div>

                <div className="pmc-report-section-card">
                    <ReportTable
                        title="Recent expenses"
                        emptyText="No posted expenses in this report range."
                        headers={['Title', 'Category', 'Date', 'Amount']}
                        rows={props.recentExpenses.map((expense) => [
                            <Link
                                key="expense"
                                href={`/expenses?search=${encodeURIComponent(expense.title)}`}
                                className="fw-semibold"
                            >
                                {expense.title}
                            </Link>,
                            expense.category,
                            humanDate(expense.incurred_on, props.app.locale),
                            currency(
                                expense.amount,
                                props.app.locale,
                                expense.currency,
                            ),
                        ])}
                    />
                </div>

                <div className="pmc-report-section-card is-full">
                    <ReportTable
                        title="Maintenance backlog"
                        emptyText="No open or in-progress maintenance requests."
                        headers={[
                            'Ticket',
                            'Asset / tenant',
                            'Priority',
                            'Status',
                        ]}
                        rows={props.maintenanceBacklog.map((request) => [
                            <Link
                                key="request"
                                href={`/maintenance-requests?search=${request.id}`}
                                className="fw-semibold"
                            >
                                #{request.id} {request.title}
                            </Link>,
                            <>
                                <div>{request.asset ?? 'No asset'}</div>
                                <div className="small text-secondary">
                                    {request.tenant ?? 'No tenant'}
                                </div>
                            </>,
                            <span className="pmc-chip pmc-chip--primary">
                                {request.priority}
                            </span>,
                            <span className="pmc-chip pmc-chip--teal">
                                {request.status}
                            </span>,
                        ])}
                    />
                </div>
            </div>
        </AdminLayout>
    );
}

function ReportSignal({
    label,
    value,
    tone,
}: {
    label: string;
    value: ReactNode;
    tone: 'good' | 'neutral' | 'risk';
}) {
    return (
        <div className={`pmc-report-signal is-${tone}`}>
            <small>{label}</small>
            <strong>{value}</strong>
        </div>
    );
}

function DecisionCard({
    icon,
    label,
    value,
    detail,
    href,
    tone = 'neutral',
}: {
    icon: string;
    label: string;
    value: ReactNode;
    detail: string;
    href: string;
    tone?: 'neutral' | 'good' | 'warn' | 'risk';
}) {
    return (
        <Link href={href} className={`pmc-report-decision-card is-${tone}`}>
            <span>
                <i className={`bi ${icon}`} />
            </span>
            <div>
                <small>{label}</small>
                <strong>{value}</strong>
                <em>{detail}</em>
            </div>
        </Link>
    );
}

function ReportMiniStat({
    icon,
    label,
    value,
}: {
    icon: string;
    label: string;
    value: ReactNode;
}) {
    return (
        <div>
            <span>
                <i className={`bi ${icon}`} />
            </span>
            <div>
                <small>{label}</small>
                <strong>{value}</strong>
            </div>
        </div>
    );
}

function ChartCard({
    title,
    empty,
    children,
}: {
    title: string;
    empty: boolean;
    children: ReactNode;
}) {
    return (
        <div className="pmc-card p-4 h-100">
            <div className="d-flex justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <div className="pmc-kicker mb-2">Analytics</div>
                    <h2 className="h5 mb-0">{title}</h2>
                </div>
            </div>
            {empty ? (
                <div className="pmc-empty-state">
                    <i className="bi bi-bar-chart" />
                    <strong>No data yet</strong>
                    <span>Change the filters or add operational records.</span>
                </div>
            ) : (
                children
            )}
        </div>
    );
}

function ReportTable({
    title,
    headers,
    rows,
    emptyText,
}: {
    title: string;
    headers: string[];
    rows: ReactNode[][];
    emptyText: string;
}) {
    return (
        <div className="pmc-card p-4 h-100">
            <div className="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div className="pmc-kicker mb-2">Report cards</div>
                    <h2 className="h5 mb-0">{title}</h2>
                </div>
                <span className="pmc-chip">{rows.length}</span>
            </div>
            {rows.length > 0 ? (
                <div className="pmc-report-card-list">
                    {rows.map((row, rowIndex) => (
                        <article
                            className="pmc-report-record-card"
                            key={rowIndex}
                        >
                            <div className="pmc-report-record-main">
                                <span>{headers[0]}</span>
                                <strong>{row[0]}</strong>
                            </div>
                            <div className="pmc-report-record-grid">
                                {row.slice(1).map((cell, index) => (
                                    <div
                                        key={`${rowIndex}-${headers[index + 1]}`}
                                    >
                                        <small>{headers[index + 1]}</small>
                                        <div className="pmc-report-record-value">
                                            {cell}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <div className="pmc-empty-state">
                    <i className="bi bi-search" />
                    <strong>No report cards</strong>
                    <span>{emptyText}</span>
                </div>
            )}
        </div>
    );
}

function buildReportActions(summary: PageProps['summary']) {
    const actions = [];

    if (summary.unpaidLeases > 0 || summary.arrears > 0) {
        actions.push({
            icon: 'bi-exclamation-triangle',
            title: 'Review arrears before they become disputes',
            detail: `${summary.unpaidLeases} leases have an unpaid balance.`,
            href: '/leases?status=active',
            tone: 'is-risk',
        });
    }

    if (summary.openRequests > 0) {
        actions.push({
            icon: 'bi-wrench-adjustable-circle',
            title: 'Triage maintenance backlog',
            detail: `${summary.openRequests} open or in-progress requests need owner or manager follow-up.`,
            href: '/maintenance-requests?status=open',
            tone: 'is-warn',
        });
    }

    if (summary.expenses > summary.revenue && summary.expenses > 0) {
        actions.push({
            icon: 'bi-graph-down-arrow',
            title: 'Audit expenses against revenue',
            detail: 'Expenses are higher than collected revenue in this report window.',
            href: '/expenses',
            tone: 'is-risk',
        });
    }

    if (summary.activeLeases === 0) {
        actions.push({
            icon: 'bi-file-earmark-plus',
            title: 'Create leases to activate reporting',
            detail: 'Assets and tenants need active leases before occupancy and revenue reports mean anything.',
            href: '/leases/create',
            tone: 'is-neutral',
        });
    }

    if (actions.length === 0) {
        actions.push({
            icon: 'bi-check2-circle',
            title: 'No urgent report exceptions',
            detail: 'Collections, expenses, and maintenance look stable for the selected range.',
            href: '/dashboard',
            tone: 'is-good',
        });
    }

    return actions;
}

function cleanFilters(filters: Record<string, string>): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([, value]) => value !== '' && value !== 'all',
        ),
    );
}
