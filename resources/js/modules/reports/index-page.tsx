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
import { compactCurrency, currency, humanDate, percent } from '@/lib/utils';
import type { SharedProps, TableFilters } from '@/types';

type ArrearsLease = {
    id: number;
    code: string;
    tenant?: string | null;
    asset?: string | null;
    arrears_amount: number;
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
        scheduledDue: number;
        scheduledPaid: number;
        collectionRate: number;
        occupancyRate: number;
        arrears: number;
        contractBalance: number;
        activeLeases: number;
        leasesInArrears: number;
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

type ReportTab = 'overview' | 'collections' | 'costs' | 'operations';

const reportTabs: Array<{
    key: ReportTab;
    label: `reports.${string}`;
    icon: string;
}> = [
    { key: 'overview', label: 'reports.tab_overview', icon: 'bi-grid' },
    {
        key: 'collections',
        label: 'reports.tab_collections',
        icon: 'bi-cash-stack',
    },
    { key: 'costs', label: 'reports.tab_costs', icon: 'bi-receipt' },
    {
        key: 'operations',
        label: 'reports.tab_operations',
        icon: 'bi-buildings',
    },
];

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
    const [presetTitleAr, setPresetTitleAr] = useState('');
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [activeTab, setActiveTab] = useState<ReportTab>(() => {
        if (typeof window === 'undefined') {
            return 'overview';
        }

        const requested = new URLSearchParams(window.location.search).get(
            'tab',
        );

        return isReportTab(requested) ? requested : 'overview';
    });
    const { locale, t, text } = useTranslator();
    const query = new URLSearchParams(cleanFilters(filters)).toString();
    const exportHref = query ? `/reports/export?${query}` : '/reports/export';
    const collectionRate = props.summary.collectionRate;

    const selectTab = (tab: ReportTab) => {
        setActiveTab(tab);

        if (typeof window === 'undefined') {
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

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

        if (!title || !presetTitleAr.trim()) {
            return;
        }

        router.post(
            '/reports/presets',
            {
                resource: 'portfolio-report',
                title_en: title,
                title_ar: presetTitleAr.trim(),
                visibility: 'private',
                is_default: false,
                filters_json: cleanFilters(filters),
            },
            { preserveScroll: true },
        );
        setPresetTitle('');
        setPresetTitleAr('');
    };

    return (
        <AdminLayout>
            <Head title={text('Reports')} />

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
                    <span>{t('reports.date_from')}</span>
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
                    <span>{t('reports.date_to')}</span>
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
                        <span>{text('Portfolio')}</span>
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
                            <option value="all">
                                {t('reports.all_portfolios')}
                            </option>
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
                        {t('reports.apply')}
                    </button>
                    <Link href="/reports" className="btn btn-outline-secondary">
                        {t('actions.reset')}
                    </Link>
                </div>
            </form>

            <nav className="pmc-report-tabs" aria-label={t('reports.sections')}>
                {reportTabs.map((tab) => (
                    <button
                        key={tab.key}
                        type="button"
                        className={activeTab === tab.key ? 'is-active' : ''}
                        aria-current={
                            activeTab === tab.key ? 'page' : undefined
                        }
                        onClick={() => selectTab(tab.key)}
                    >
                        <i className={`bi ${tab.icon}`} />
                        {t(tab.label)}
                    </button>
                ))}
            </nav>

            {activeTab === 'overview' ? (
                <>
                    <MetricGrid
                        metrics={[
                            {
                                label: 'Collected',
                                value: compactCurrency(
                                    props.summary.revenue,
                                    props.app.locale,
                                ),
                                detail: t(
                                    'reports.collection_health_value',
                                    undefined,
                                    { value: percent(collectionRate) },
                                ),
                                icon: 'bi-cash-stack',
                                tone: 'ink',
                                href: '/payments',
                            },
                            {
                                label: 'Expenses',
                                value: compactCurrency(
                                    props.summary.expenses,
                                    props.app.locale,
                                ),
                                detail: t('reports.recent_costs', undefined, {
                                    count: props.recentExpenses.length,
                                }),
                                icon: 'bi-receipt',
                                tone: 'amber',
                                href: '/expenses',
                            },
                            {
                                label: 'Net position',
                                value: compactCurrency(
                                    props.summary.net,
                                    props.app.locale,
                                ),
                                detail: t(
                                    'reports.occupancy_value',
                                    undefined,
                                    {
                                        value: percent(
                                            props.summary.occupancyRate,
                                        ),
                                    },
                                ),
                                icon: 'bi-graph-up-arrow',
                                tone: props.summary.net >= 0 ? 'teal' : 'red',
                            },
                            {
                                label: 'Arrears',
                                value: compactCurrency(
                                    props.summary.arrears,
                                    props.app.locale,
                                ),
                                detail: t('reports.arrears_count', undefined, {
                                    count: props.summary.leasesInArrears,
                                }),
                                icon: 'bi-exclamation-circle',
                                tone:
                                    props.summary.arrears > 0 ? 'red' : 'blue',
                                href: '/leases',
                            },
                        ]}
                    />

                    <section className="pmc-report-pulse-grid">
                        <ReportPulse
                            label="Collection health"
                            value={percent(collectionRate)}
                            detail={t('reports.scheduled_paid', undefined, {
                                paid: currency(
                                    props.summary.scheduledPaid,
                                    locale,
                                ),
                                due: currency(
                                    props.summary.scheduledDue,
                                    locale,
                                ),
                            })}
                            icon="bi-wallet2"
                            tone={collectionRate >= 80 ? 'good' : 'risk'}
                        />
                        <ReportPulse
                            label="Occupancy"
                            value={percent(props.summary.occupancyRate)}
                            detail={t('reports.active_leases', undefined, {
                                count: props.summary.activeLeases,
                            })}
                            icon="bi-building-check"
                            tone={
                                props.summary.occupancyRate >= 70
                                    ? 'good'
                                    : 'warn'
                            }
                        />
                        <ReportPulse
                            label="Service backlog"
                            value={props.summary.openRequests.toLocaleString()}
                            detail={t('reports.resolved_count', undefined, {
                                count: props.summary.resolvedRequests,
                            })}
                            icon="bi-tools"
                            tone={
                                props.summary.openRequests > 0 ? 'warn' : 'good'
                            }
                        />
                        <ReportPulse
                            label={t('reports.contracts_in_arrears')}
                            value={props.summary.leasesInArrears.toLocaleString()}
                            detail={t('reports.contract_balance', undefined, {
                                amount: currency(
                                    props.summary.contractBalance,
                                    locale,
                                ),
                            })}
                            icon="bi-file-earmark-excel"
                            tone={
                                props.summary.leasesInArrears > 0
                                    ? 'risk'
                                    : 'good'
                            }
                        />
                    </section>
                </>
            ) : null}

            {activeTab === 'collections' ? (
                <>
                    <div className="pmc-report-breakdown-grid is-single">
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
                    </div>
                    <div className="pmc-report-record-grid">
                        <ReportRecordSection
                            title={t('reports.contracts_in_arrears')}
                            description={t('reports.arrears_description')}
                            empty={t('reports.no_arrears')}
                            rows={props.arrearsLeases.map((lease) => ({
                                href: `/leases/${lease.id}`,
                                title: lease.code,
                                meta: `${lease.tenant ?? text('No tenant')} · ${lease.asset ?? text('No asset')}`,
                                value: currency(
                                    lease.arrears_amount,
                                    props.app.locale,
                                    lease.currency,
                                ),
                                tone: 'danger',
                            }))}
                        />
                        <ReportRecordSection
                            title="Recent payments"
                            description="Latest posted collections."
                            empty="No posted payments in this range."
                            rows={props.recentPayments.map((payment) => ({
                                href: `/payments/${payment.id}`,
                                title: payment.reference,
                                meta: `${payment.tenant ?? text('No tenant')} · ${humanDate(payment.received_on, locale)}`,
                                value: currency(
                                    payment.amount,
                                    props.app.locale,
                                    payment.currency,
                                ),
                                tone: 'success',
                            }))}
                        />
                        <ReportRecordSection
                            title="Top assets"
                            description="Assets producing the most revenue."
                            empty="No revenue-producing assets in this range."
                            rows={props.topAssets.map((asset, index) => ({
                                href: '/assets',
                                title:
                                    asset.asset ||
                                    t('reports.asset_number', undefined, {
                                        number: index + 1,
                                    }),
                                meta: t('reports.lease_count', undefined, {
                                    count: asset.lease_count,
                                }),
                                value: currency(
                                    asset.revenue,
                                    props.app.locale,
                                    asset.currency,
                                ),
                                tone: 'success',
                            }))}
                        />
                    </div>
                </>
            ) : null}

            {activeTab === 'costs' ? (
                <>
                    <div className="pmc-report-breakdown-grid is-single">
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
                    </div>
                    <div className="pmc-report-record-grid">
                        <ReportRecordSection
                            title="Recent expenses"
                            description="Latest posted costs."
                            empty="No posted expenses in this range."
                            rows={props.recentExpenses.map((expense) => ({
                                href: `/expenses/${expense.id}`,
                                title: expense.title,
                                meta: `${text(humanLabel(expense.category))} · ${expense.asset ?? text('No asset')}`,
                                value: currency(
                                    expense.amount,
                                    props.app.locale,
                                    expense.currency,
                                ),
                                tone: 'warning',
                            }))}
                        />
                    </div>
                </>
            ) : null}

            {activeTab === 'operations' ? (
                <>
                    <div className="pmc-report-breakdown-grid">
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
                            <BreakdownCards
                                source={props.charts.maintenanceByStatus}
                            />
                        </WorkspacePanel>
                    </div>
                    <div className="pmc-report-record-grid">
                        <ReportRecordSection
                            title="Maintenance backlog"
                            description="Open work that needs follow-up."
                            empty="No open maintenance requests."
                            rows={props.maintenanceBacklog.map((request) => ({
                                href: `/maintenance-requests/${request.id}`,
                                title: request.title,
                                meta: `${request.asset ?? text('No asset')} · ${text(humanLabel(request.priority))}`,
                                value: text(humanLabel(request.status)),
                                status: request.status,
                            }))}
                        />
                    </div>
                </>
            ) : null}

            <details className="pmc-report-presets-compact">
                <summary>
                    <div>
                        <i className="bi bi-bookmark" />
                        <span>{t('reports.saved_views')}</span>
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
                            {t('reports.preset_name_en')}
                        </label>
                        <input
                            id="report-preset-title"
                            className="form-control"
                            value={presetTitle}
                            placeholder={t('reports.preset_name_en')}
                            onChange={(event) =>
                                setPresetTitle(event.currentTarget.value)
                            }
                        />
                        <label
                            className="visually-hidden"
                            htmlFor="report-preset-title-ar"
                        >
                            {t('reports.preset_name_ar')}
                        </label>
                        <input
                            id="report-preset-title-ar"
                            className="form-control"
                            dir="rtl"
                            value={presetTitleAr}
                            placeholder={t('reports.preset_name_ar')}
                            onChange={(event) =>
                                setPresetTitleAr(event.currentTarget.value)
                            }
                        />
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={savePreset}
                        >
                            {t('reports.save_filters')}
                        </button>
                    </div>
                    <div className="pmc-report-preset-list">
                        {props.savedPresets.length > 0 ? (
                            props.savedPresets.map((preset) => (
                                <article key={preset.id}>
                                    <div>
                                        <strong>
                                            {locale === 'ar'
                                                ? preset.title_ar ||
                                                  preset.title_en
                                                : preset.title_en ||
                                                  preset.title_ar}
                                        </strong>
                                        <span>
                                            {text(
                                                humanLabel(preset.visibility),
                                            )}
                                        </span>
                                    </div>
                                    <Link href={preset.url}>
                                        {t('actions.open')}
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.delete(
                                                `/reports/presets/${preset.id}`,
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        {t('reports.remove')}
                                    </button>
                                </article>
                            ))
                        ) : (
                            <p>{t('reports.no_saved_views')}</p>
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
    const { text } = useTranslator();

    return (
        <article className={`pmc-report-pulse is-${tone}`}>
            <i className={`bi ${icon}`} />
            <div>
                <span>{text(label)}</span>
                <strong>{value}</strong>
                <small>{text(detail)}</small>
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
    const { text } = useTranslator();
    const entries = Object.entries(source);
    const maximum = Math.max(...entries.map(([, value]) => value), 1);

    if (entries.length === 0) {
        return <ReportEmpty>{text('No data in this range.')}</ReportEmpty>;
    }

    return (
        <div className="pmc-report-bars">
            {entries.map(([label, value]) => (
                <div key={label}>
                    <div>
                        <span>{text(humanLabel(label))}</span>
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
    const { text } = useTranslator();
    const entries = Object.entries(source);

    if (entries.length === 0) {
        return <ReportEmpty>{text('No data in this range.')}</ReportEmpty>;
    }

    return (
        <div className="pmc-report-breakdown-cards">
            {entries.map(([label, value]) => (
                <div key={label}>
                    <span>{text(humanLabel(label))}</span>
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
    const { text } = useTranslator();

    return (
        <div className="pmc-command-empty">
            {typeof children === 'string' ? text(children) : children}
        </div>
    );
}

function cleanFilters(filters: Record<string, string>) {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([, value]) => value !== '' && value !== 'all',
        ),
    );
}

function isReportTab(value: string | null): value is ReportTab {
    return reportTabs.some((tab) => tab.key === value);
}
