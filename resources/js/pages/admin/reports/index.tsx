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

import { PageHeader } from '@/components/page-header';
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

    return (
        <AdminLayout>
            <Head title="Reports" />
            <PageHeader
                title="Reports"
                description="Filter, export, and explain portfolio performance across rent, expenses, arrears, assets, and maintenance."
                actions={
                    <>
                        <a
                            href={exportHref}
                            className="btn btn-outline-secondary"
                        >
                            <i className="bi bi-download me-2" />
                            Export report
                        </a>
                        <Link href="/payments" className="btn btn-primary">
                            Post payment
                        </Link>
                    </>
                }
            />

            <form className="pmc-card p-3 p-lg-4 mb-4" onSubmit={applyFilters}>
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

            <div className="row g-4">
                <div className="col-xl-7">
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

                <div className="col-xl-5">
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

                <div className="col-xl-6">
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

                <div className="col-xl-6">
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

                <div className="col-xl-7">
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

                <div className="col-xl-5">
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

                <div className="col-xl-6">
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

                <div className="col-xl-6">
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

                <div className="col-12">
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
                    <div className="pmc-kicker mb-2">Report table</div>
                    <h2 className="h5 mb-0">{title}</h2>
                </div>
                <span className="pmc-chip">{rows.length}</span>
            </div>
            <div className="pmc-table-scroll">
                <table className="pmc-data-table table">
                    <thead>
                        <tr>
                            {headers.map((header) => (
                                <th key={header}>{header}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length > 0 ? (
                            rows.map((row, rowIndex) => (
                                <tr key={rowIndex}>
                                    {row.map((cell, index) => (
                                        <td
                                            key={`${rowIndex}-${headers[index]}`}
                                            data-label={headers[index]}
                                        >
                                            {cell}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td
                                    className="pmc-empty-cell"
                                    colSpan={headers.length}
                                >
                                    <div className="pmc-empty-state">
                                        <i className="bi bi-search" />
                                        <strong>No report rows</strong>
                                        <span>{emptyText}</span>
                                    </div>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function cleanFilters(filters: Record<string, string>): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([, value]) => value !== '' && value !== 'all',
        ),
    );
}
