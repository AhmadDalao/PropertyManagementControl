import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

import {
    MetricGrid,
    StatusBadge,
    WorkspaceHeader,
    WorkspacePanel,
    humanLabel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate, percent } from '@/lib/utils';
import type { SharedProps, TableFilters } from '@/types';

type ArrearsLease = {
    id: number;
    code: string;
    tenant?: string | null;
    asset?: string | null;
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
    visibility: string;
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

export default function ReportsIndexPage() {
    const { props } = usePage<PageProps>();
    const [filters, setFilters] = useState({
        date_from: String(props.filters.date_from ?? ''),
        date_to: String(props.filters.date_to ?? ''),
        portfolio_id: props.filters.portfolio_id
            ? String(props.filters.portfolio_id)
            : 'all',
    });
    const [presetTitle, setPresetTitle] = useState('');
    const [filtersOpen, setFiltersOpen] = useState(false);
    const { t } = useTranslator();
    const query = new URLSearchParams(cleanFilters(filters)).toString();
    const exportHref = query ? `/reports/export?${query}` : '/reports/export';
    const collectionBase = props.summary.revenue + props.summary.arrears;
    const collectionRate =
        collectionBase > 0
            ? (props.summary.revenue / collectionBase) * 100
            : props.summary.revenue > 0
              ? 100
              : 0;

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get('/reports', cleanFilters(filters), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

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

            <WorkspaceHeader
                eyebrow="Overview"
                title="Reports"
                description="One operating view for collections, expenses, occupancy, arrears, and maintenance, scoped to the selected portfolio and dates."
                actions={[
                    {
                        label: 'Report guide',
                        href: '/documentation',
                        icon: 'bi-question-circle',
                        tone: 'quiet',
                    },
                    {
                        label: 'Export Excel (.xlsx)',
                        href: exportHref,
                        icon: 'bi-file-earmark-excel',
                        tone: 'primary',
                        native: true,
                    },
                ]}
            />

            <button
                type="button"
                className="pmc-report-filter-trigger"
                aria-expanded={filtersOpen}
                onClick={() => setFiltersOpen((open) => !open)}
            >
                <i className="bi bi-sliders2" />
                {filtersOpen
                    ? t('reports.hide_filters', 'Hide filters')
                    : t('reports.show_filters', 'Show filters')}
            </button>

            <form
                className={`pmc-report-toolbar ${filtersOpen ? 'is-open' : ''}`}
                onSubmit={applyFilters}
            >
                <label>
                    <span>Date from</span>
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
                </label>
                <label>
                    <span>Date to</span>
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
                </label>
                {props.mode === 'superadmin' ? (
                    <label>
                        <span>Portfolio</span>
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
                                <option key={portfolio.id} value={portfolio.id}>
                                    {portfolio.name}
                                </option>
                            ))}
                        </select>
                    </label>
                ) : null}
                <div className="pmc-report-toolbar-actions">
                    <button className="btn btn-primary">
                        <i className="bi bi-funnel me-2" />
                        Apply
                    </button>
                    <Link href="/reports" className="btn btn-outline-secondary">
                        Reset
                    </Link>
                </div>
            </form>

            <MetricGrid
                metrics={[
                    {
                        label: 'Collected',
                        value: currency(
                            props.summary.revenue,
                            props.app.locale,
                        ),
                        detail: `${percent(collectionRate)} collection health`,
                        icon: 'bi-cash-stack',
                        tone: 'ink',
                        href: '/payments',
                    },
                    {
                        label: 'Expenses',
                        value: currency(
                            props.summary.expenses,
                            props.app.locale,
                        ),
                        detail: `${props.recentExpenses.length} recent costs`,
                        icon: 'bi-receipt',
                        tone: 'amber',
                        href: '/expenses',
                    },
                    {
                        label: 'Net position',
                        value: currency(props.summary.net, props.app.locale),
                        detail: `${percent(props.summary.occupancyRate)} occupancy`,
                        icon: 'bi-graph-up-arrow',
                        tone: props.summary.net >= 0 ? 'teal' : 'red',
                    },
                    {
                        label: 'Arrears',
                        value: currency(
                            props.summary.arrears,
                            props.app.locale,
                        ),
                        detail: `${props.summary.unpaidLeases} unpaid leases`,
                        icon: 'bi-exclamation-circle',
                        tone: props.summary.arrears > 0 ? 'red' : 'blue',
                        href: '/leases',
                    },
                ]}
            />

            <section className="pmc-report-pulse-grid">
                <ReportPulse
                    label="Collection health"
                    value={percent(collectionRate)}
                    detail={`${currency(props.summary.revenue, props.app.locale)} received`}
                    icon="bi-wallet2"
                    tone={collectionRate >= 80 ? 'good' : 'risk'}
                />
                <ReportPulse
                    label="Occupancy"
                    value={percent(props.summary.occupancyRate)}
                    detail={`${props.summary.activeLeases} active leases`}
                    icon="bi-building-check"
                    tone={props.summary.occupancyRate >= 70 ? 'good' : 'warn'}
                />
                <ReportPulse
                    label="Service backlog"
                    value={props.summary.openRequests.toLocaleString()}
                    detail={`${props.summary.resolvedRequests} resolved`}
                    icon="bi-tools"
                    tone={props.summary.openRequests > 0 ? 'warn' : 'good'}
                />
                <ReportPulse
                    label="Unpaid leases"
                    value={props.summary.unpaidLeases.toLocaleString()}
                    detail="Contracts with a remaining balance"
                    icon="bi-file-earmark-excel"
                    tone={props.summary.unpaidLeases > 0 ? 'risk' : 'good'}
                />
            </section>

            <div className="pmc-report-breakdown-grid">
                <WorkspacePanel
                    eyebrow="Revenue"
                    title="Monthly collections"
                    description="Posted payments by month."
                >
                    <BreakdownBars
                        source={props.charts.revenueByMonth}
                        format={(value) =>
                            currency(value, props.app.locale, 'SAR')
                        }
                    />
                </WorkspacePanel>
                <WorkspacePanel
                    eyebrow="Costs"
                    title="Expense categories"
                    description="Posted expenses by category."
                >
                    <BreakdownBars
                        source={props.charts.expenseByCategory}
                        format={(value) =>
                            currency(value, props.app.locale, 'SAR')
                        }
                    />
                </WorkspacePanel>
                <WorkspacePanel
                    eyebrow="Portfolio"
                    title="Asset mix"
                    description="Property records by type."
                >
                    <BreakdownCards source={props.charts.assetMix} />
                </WorkspacePanel>
                <WorkspacePanel
                    eyebrow="Service"
                    title="Maintenance status"
                    description="Requests in the selected period."
                >
                    <BreakdownCards source={props.charts.maintenanceByStatus} />
                </WorkspacePanel>
            </div>

            <div className="pmc-report-record-grid">
                <ReportRecordSection
                    title="Arrears"
                    description="Largest unpaid lease balances."
                    empty="No unpaid lease balances."
                    rows={props.arrearsLeases.map((lease) => ({
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
                <ReportRecordSection
                    title="Top assets"
                    description="Assets producing the most revenue."
                    empty="No revenue-producing assets in this range."
                    rows={props.topAssets.map((asset, index) => ({
                        href: '/assets',
                        title: asset.asset || `Asset ${index + 1}`,
                        meta: `${asset.lease_count} lease${asset.lease_count === 1 ? '' : 's'}`,
                        value: currency(
                            asset.revenue,
                            props.app.locale,
                            asset.currency,
                        ),
                        tone: 'success',
                    }))}
                />
                <ReportRecordSection
                    title="Recent payments"
                    description="Latest posted collections."
                    empty="No posted payments in this range."
                    rows={props.recentPayments.map((payment) => ({
                        href: `/payments/${payment.id}`,
                        title: payment.reference,
                        meta: `${payment.tenant ?? 'No tenant'} · ${humanDate(payment.received_on, props.app.locale)}`,
                        value: currency(
                            payment.amount,
                            props.app.locale,
                            payment.currency,
                        ),
                        tone: 'success',
                    }))}
                />
                <ReportRecordSection
                    title="Recent expenses"
                    description="Latest posted costs."
                    empty="No posted expenses in this range."
                    rows={props.recentExpenses.map((expense) => ({
                        href: `/expenses/${expense.id}`,
                        title: expense.title,
                        meta: `${humanLabel(expense.category)} · ${expense.asset ?? 'No asset'}`,
                        value: currency(
                            expense.amount,
                            props.app.locale,
                            expense.currency,
                        ),
                        tone: 'warning',
                    }))}
                />
                <ReportRecordSection
                    title="Maintenance backlog"
                    description="Open work that needs follow-up."
                    empty="No open maintenance requests."
                    rows={props.maintenanceBacklog.map((request) => ({
                        href: `/maintenance-requests/${request.id}`,
                        title: request.title,
                        meta: `${request.asset ?? 'No asset'} · ${humanLabel(request.priority)}`,
                        value: humanLabel(request.status),
                        status: request.status,
                    }))}
                />
            </div>

            <details className="pmc-report-presets-compact">
                <summary>
                    <div>
                        <i className="bi bi-bookmark" />
                        <span>Saved report views</span>
                        <strong>{props.savedPresets.length}</strong>
                    </div>
                    <i className="bi bi-chevron-down" />
                </summary>
                <div className="pmc-report-presets-body">
                    <div className="pmc-report-preset-create">
                        <label
                            className="visually-hidden"
                            htmlFor="report-preset-title"
                        >
                            Preset name
                        </label>
                        <input
                            id="report-preset-title"
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
                            Save current filters
                        </button>
                    </div>
                    <div className="pmc-report-preset-list">
                        {props.savedPresets.length > 0 ? (
                            props.savedPresets.map((preset) => (
                                <article key={preset.id}>
                                    <div>
                                        <strong>{preset.title_en}</strong>
                                        <span>{preset.visibility}</span>
                                    </div>
                                    <Link href={preset.url}>Open</Link>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.delete(
                                                `/reports/presets/${preset.id}`,
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        Remove
                                    </button>
                                </article>
                            ))
                        ) : (
                            <p>No saved views yet.</p>
                        )}
                    </div>
                </div>
            </details>
        </AdminLayout>
    );
}

function ReportPulse({
    label,
    value,
    detail,
    icon,
    tone,
}: {
    label: string;
    value: string;
    detail: string;
    icon: string;
    tone: 'good' | 'warn' | 'risk';
}) {
    return (
        <article className={`pmc-report-pulse is-${tone}`}>
            <i className={`bi ${icon}`} />
            <div>
                <span>{label}</span>
                <strong>{value}</strong>
                <small>{detail}</small>
            </div>
        </article>
    );
}

function BreakdownBars({
    source,
    format,
}: {
    source: Record<string, number>;
    format: (value: number) => string;
}) {
    const entries = Object.entries(source);
    const maximum = Math.max(...entries.map(([, value]) => value), 1);

    if (entries.length === 0) {
        return <ReportEmpty>No data in this range.</ReportEmpty>;
    }

    return (
        <div className="pmc-report-bars">
            {entries.map(([label, value]) => (
                <div key={label}>
                    <div>
                        <span>{humanLabel(label)}</span>
                        <strong>{format(value)}</strong>
                    </div>
                    <div>
                        <i style={{ width: `${(value / maximum) * 100}%` }} />
                    </div>
                </div>
            ))}
        </div>
    );
}

function BreakdownCards({ source }: { source: Record<string, number> }) {
    const entries = Object.entries(source);

    if (entries.length === 0) {
        return <ReportEmpty>No data in this range.</ReportEmpty>;
    }

    return (
        <div className="pmc-report-breakdown-cards">
            {entries.map(([label, value]) => (
                <div key={label}>
                    <span>{humanLabel(label)}</span>
                    <strong>{value}</strong>
                </div>
            ))}
        </div>
    );
}

function ReportRecordSection({
    title,
    description,
    rows,
    empty,
}: {
    title: string;
    description: string;
    rows: Array<{
        href: string;
        title: string;
        meta: string;
        value: string;
        tone?: 'success' | 'warning' | 'danger';
        status?: string;
    }>;
    empty: string;
}) {
    return (
        <WorkspacePanel
            title={title}
            description={description}
            className="pmc-report-record-panel"
        >
            {rows.length > 0 ? (
                <div className="pmc-report-record-cards">
                    {rows.slice(0, 6).map((row) => (
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
                            <i className="bi bi-arrow-up-right" />
                        </Link>
                    ))}
                </div>
            ) : (
                <ReportEmpty>{empty}</ReportEmpty>
            )}
        </WorkspacePanel>
    );
}

function ReportEmpty({ children }: { children: ReactNode }) {
    return <div className="pmc-command-empty">{children}</div>;
}

function cleanFilters(filters: Record<string, string>) {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([, value]) => value !== '' && value !== 'all',
        ),
    );
}
