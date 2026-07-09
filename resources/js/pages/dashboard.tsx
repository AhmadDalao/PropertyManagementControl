import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import { Bar, Doughnut } from 'react-chartjs-2';

import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency, humanDate } from '@/lib/utils';
import type { SharedProps } from '@/types';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    ArcElement,
    Tooltip,
    Legend,
);

type LeaseBalance = {
    id: number;
    code: string;
    tenant?: string | null;
    asset?: string | null;
    ends_at?: string | null;
    days_remaining?: number | null;
    balance_remaining: number;
    currency: string;
};

type DashboardPageProps = SharedProps & {
    mode: 'tenant' | 'portfolio' | 'superadmin';
    stats: Record<string, number | string | null>;
    charts?: {
        occupancy?: Record<string, number>;
        paymentHealth?: Array<{
            code: string;
            tenant?: string | null;
            due: number;
            paid: number;
            remaining: number;
        }>;
        assetMix?: Record<string, number>;
        maintenanceByStatus?: Record<string, number>;
    };
    setupChecklist?: Array<{
        label: string;
        done: boolean;
        href: string;
    }>;
    cmsStatus?: {
        published: number;
        draft: number;
        homepage?: string | null;
    };
    expiringLeases?: LeaseBalance[];
    arrearsLeases?: LeaseBalance[];
    recentPayments?: Array<{
        id: number;
        amount: number;
        currency: string;
        received_on: string;
        tenant_profile?: { user?: { name: string } };
    }>;
    recentMaintenance?: Array<{
        id: number;
        title: string;
        status: string;
        priority?: string;
        created_at: string;
        asset?: { title_en: string };
    }>;
    tenantPortal?: {
        lease?: {
            code: string;
            days_remaining: number;
            balance_remaining: number;
            total_paid?: number;
            rent_amount?: number;
            currency?: string;
            started_at?: string;
            ends_at?: string;
            leaseable?: { title_en?: string; code?: string } | null;
        } | null;
        documents?: Array<{ id: number; title_en: string; type: string }>;
        payments?: Array<{
            id: number;
            amount: number;
            currency: string;
            received_on: string;
            reference?: string;
        }>;
        requests?: Array<{
            id: number;
            title: string;
            status: string;
            created_at: string;
        }>;
    };
};

export default function DashboardPage() {
    const { props } = usePage<DashboardPageProps>();

    if (props.mode === 'tenant') {
        return <TenantDashboard props={props} />;
    }

    return <OperationsDashboard props={props} />;
}

function OperationsDashboard({ props }: { props: DashboardPageProps }) {
    const occupancySource = props.charts?.occupancy ?? {};
    const assetMixSource = props.charts?.assetMix ?? {};
    const maintenanceSource = props.charts?.maintenanceByStatus ?? {};
    const paymentHealth = props.charts?.paymentHealth ?? [];
    const recentPayments = props.recentPayments ?? [];
    const recentMaintenance = props.recentMaintenance ?? [];
    const expiringLeases = props.expiringLeases ?? [];
    const arrearsLeases = props.arrearsLeases ?? [];
    const setupChecklist = props.setupChecklist ?? [];

    return (
        <AdminLayout>
            <Head title="Dashboard" />
            <PageHeader
                title={
                    props.mode === 'superadmin'
                        ? 'Platform Control Center'
                        : 'Portfolio Control Center'
                }
                description="A guided operating view for property value, rent health, occupancy, tenant activity, and website readiness."
                actions={
                    <>
                        <Link href="/assets" className="btn btn-primary">
                            <i className="bi bi-plus-lg me-2" />
                            Add asset
                        </Link>
                        <Link
                            href="/documentation"
                            className="btn btn-outline-secondary"
                        >
                            Open docs
                        </Link>
                    </>
                }
            />

            <section className="pmc-command-hero">
                <div>
                    <div className="pmc-kicker mb-2">Today</div>
                    <h2>
                        {props.mode === 'superadmin'
                            ? 'See every portfolio before problems become noise.'
                            : 'Run the portfolio from assets to service requests.'}
                    </h2>
                    <p>
                        Follow the checklist, watch arrears and expiring leases,
                        and use quick actions instead of hunting through menus.
                    </p>
                </div>
                <div className="pmc-command-actions">
                    <Link href="/tenants" className="btn btn-light">
                        <i className="bi bi-person-plus me-2" />
                        Add tenant
                    </Link>
                    <Link href="/leases" className="btn btn-light">
                        <i className="bi bi-file-earmark-plus me-2" />
                        Create lease
                    </Link>
                    <Link href="/payments" className="btn btn-light">
                        <i className="bi bi-cash-stack me-2" />
                        Post payment
                    </Link>
                </div>
            </section>

            <div className="row g-3 mb-4">
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Assets"
                        value={props.stats.totalAssets}
                        hint={`${props.stats.vacantUnits ?? 0} rentable vacant`}
                        tone="accent"
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Portfolio value"
                        value={currency(
                            Number(props.stats.totalValue ?? 0),
                            props.app.locale,
                        )}
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Monthly revenue"
                        value={currency(
                            Number(props.stats.monthlyRevenue ?? 0),
                            props.app.locale,
                        )}
                        hint={`${currency(Number(props.stats.monthlyExpenses ?? 0), props.app.locale)} expenses`}
                        tone="teal"
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Open issues"
                        value={props.stats.openRequests}
                        hint={`${currency(Number(props.stats.arrears ?? 0), props.app.locale)} outstanding`}
                    />
                </div>
            </div>

            <div className="pmc-dashboard-grid">
                <section className="pmc-card p-4 pmc-dashboard-span-4">
                    <SectionTitle
                        eyebrow="Setup"
                        title="Onboarding checklist"
                        actionHref="/documentation"
                        actionLabel="Read guide"
                    />
                    <div className="pmc-checklist">
                        {setupChecklist.map((item) => (
                            <Link
                                key={item.label}
                                href={item.href}
                                className={item.done ? 'is-done' : ''}
                            >
                                <i
                                    className={`bi ${
                                        item.done
                                            ? 'bi-check-circle-fill'
                                            : 'bi-circle'
                                    }`}
                                />
                                <span>{item.label}</span>
                            </Link>
                        ))}
                    </div>
                </section>

                <section className="pmc-card p-4 pmc-dashboard-span-4">
                    <SectionTitle
                        eyebrow="Occupancy"
                        title="Asset occupancy"
                        actionHref="/assets"
                        actionLabel="Open assets"
                    />
                    {Object.keys(occupancySource).length > 0 ? (
                        <Doughnut
                            data={{
                                labels: Object.keys(occupancySource),
                                datasets: [
                                    {
                                        data: Object.values(occupancySource),
                                        backgroundColor: chartColors,
                                    },
                                ],
                            }}
                        />
                    ) : (
                        <ChartEmptyState
                            icon="bi-buildings"
                            title="No assets yet"
                            message="Create buildings, floors, and units to unlock occupancy charts."
                        />
                    )}
                </section>

                <section className="pmc-card p-4 pmc-dashboard-span-4">
                    <SectionTitle
                        eyebrow="Rent health"
                        title="Paid vs remaining"
                        actionHref="/payments"
                        actionLabel="Payments"
                    />
                    {paymentHealth.length > 0 ? (
                        <Bar
                            data={{
                                labels: paymentHealth.map((item) => item.code),
                                datasets: [
                                    {
                                        label: 'Paid',
                                        data: paymentHealth.map(
                                            (item) => item.paid,
                                        ),
                                        backgroundColor: '#0c8a7c',
                                    },
                                    {
                                        label: 'Remaining',
                                        data: paymentHealth.map(
                                            (item) => item.remaining,
                                        ),
                                        backgroundColor: '#ef6c2f',
                                    },
                                ],
                            }}
                        />
                    ) : (
                        <ChartEmptyState
                            icon="bi-file-earmark-text"
                            title="No leases yet"
                            message="Create leases to compare due rent, paid rent, and remaining balances."
                        />
                    )}
                </section>

                <section className="pmc-card p-4 pmc-dashboard-span-6">
                    <SectionTitle
                        eyebrow="Expiry risk"
                        title="Leases ending within 90 days"
                        actionHref="/leases"
                        actionLabel="Open leases"
                    />
                    <LeaseList
                        leases={expiringLeases}
                        locale={props.app.locale}
                        empty="No active leases are expiring soon."
                    />
                </section>

                <section className="pmc-card p-4 pmc-dashboard-span-6">
                    <SectionTitle
                        eyebrow="Collections"
                        title="Highest outstanding balances"
                        actionHref="/payments"
                        actionLabel="Post payment"
                    />
                    <LeaseList
                        leases={arrearsLeases}
                        locale={props.app.locale}
                        empty="No outstanding balances found."
                        showBalanceOnly
                    />
                </section>

                <section className="pmc-card p-4 pmc-dashboard-span-4">
                    <SectionTitle eyebrow="Assets" title="Asset mix" />
                    <MiniMetricList
                        source={assetMixSource}
                        empty="Asset types will appear after seeding or creating assets."
                    />
                </section>

                <section className="pmc-card p-4 pmc-dashboard-span-4">
                    <SectionTitle
                        eyebrow="Maintenance"
                        title="Request status"
                        actionHref="/maintenance-requests"
                        actionLabel="Open queue"
                    />
                    <MiniMetricList
                        source={maintenanceSource}
                        empty="No maintenance requests are open."
                    />
                </section>

                <section className="pmc-card p-4 pmc-dashboard-span-4">
                    <SectionTitle
                        eyebrow="Website"
                        title="CMS publishing"
                        actionHref="/cms"
                        actionLabel="Builder"
                    />
                    <div className="pmc-cms-status">
                        <div>
                            <span>Published pages</span>
                            <strong>{props.cmsStatus?.published ?? 0}</strong>
                        </div>
                        <div>
                            <span>Draft pages</span>
                            <strong>{props.cmsStatus?.draft ?? 0}</strong>
                        </div>
                        <div>
                            <span>Homepage</span>
                            <strong>
                                {props.cmsStatus?.homepage ?? 'Not set'}
                            </strong>
                        </div>
                    </div>
                </section>

                <section className="pmc-card p-4 pmc-dashboard-span-6">
                    <SectionTitle
                        eyebrow="Payments"
                        title="Latest receipts"
                        actionHref="/payments"
                        actionLabel="View all"
                    />
                    <ActivityTable
                        empty="No payments recorded yet."
                        rows={recentPayments.map((payment) => ({
                            id: payment.id,
                            title:
                                payment.tenant_profile?.user?.name ??
                                `Receipt #${payment.id}`,
                            meta: humanDate(
                                payment.received_on,
                                props.app.locale,
                            ),
                            value: currency(
                                payment.amount,
                                props.app.locale,
                                payment.currency,
                            ),
                        }))}
                    />
                </section>

                <section className="pmc-card p-4 pmc-dashboard-span-6">
                    <SectionTitle
                        eyebrow="Service"
                        title="Maintenance backlog"
                        actionHref="/maintenance-requests"
                        actionLabel="View queue"
                    />
                    <ActivityTable
                        empty="No maintenance requests are open."
                        rows={recentMaintenance.map((item) => ({
                            id: item.id,
                            title: item.title,
                            meta: item.asset?.title_en ?? 'No asset',
                            value: item.status,
                        }))}
                    />
                </section>
            </div>
        </AdminLayout>
    );
}

function TenantDashboard({ props }: { props: DashboardPageProps }) {
    const lease = props.tenantPortal?.lease;
    const payments = props.tenantPortal?.payments ?? [];
    const requests = props.tenantPortal?.requests ?? [];
    const documents = props.tenantPortal?.documents ?? [];

    return (
        <AdminLayout>
            <Head title="Tenant Dashboard" />
            <PageHeader
                title="Tenant Portal"
                description="Your rent, contract period, documents, payment history, and maintenance requests in one place."
                actions={
                    <Link
                        href="/maintenance-requests"
                        className="btn btn-primary"
                    >
                        <i className="bi bi-tools me-2" />
                        Request maintenance
                    </Link>
                }
            />

            <section className="pmc-tenant-hero">
                <div>
                    <div className="pmc-kicker mb-2">Current rental</div>
                    <h2>{lease?.leaseable?.title_en ?? 'No active lease'}</h2>
                    <p>
                        {lease
                            ? `${lease.code} · ${lease.leaseable?.code ?? 'Asset'}`
                            : 'Ask your property owner to create your lease and portal access.'}
                    </p>
                </div>
                <div className="pmc-tenant-meter">
                    <span>Contract days left</span>
                    <strong>{props.stats.daysLeft ?? 0}</strong>
                </div>
            </section>

            <div className="row g-3 mb-4">
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Lease"
                        value={props.stats.leaseCode}
                        tone="accent"
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Amount left"
                        value={currency(
                            Number(props.stats.amountLeft ?? 0),
                            props.app.locale,
                            lease?.currency ?? 'SAR',
                        )}
                        tone="teal"
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Paid"
                        value={currency(
                            Number(props.stats.paidAmount ?? 0),
                            props.app.locale,
                            lease?.currency ?? 'SAR',
                        )}
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Requests"
                        value={props.stats.maintenanceRequests}
                    />
                </div>
            </div>

            <div className="pmc-dashboard-grid">
                <section className="pmc-card p-4 pmc-dashboard-span-6">
                    <SectionTitle
                        eyebrow="Contract"
                        title="Lease period and documents"
                    />
                    <div className="pmc-tenant-contract">
                        <div>
                            <span>Starts</span>
                            <strong>
                                {humanDate(lease?.started_at, props.app.locale)}
                            </strong>
                        </div>
                        <div>
                            <span>Ends</span>
                            <strong>
                                {humanDate(lease?.ends_at, props.app.locale)}
                            </strong>
                        </div>
                        <div>
                            <span>Monthly rent</span>
                            <strong>
                                {currency(
                                    Number(lease?.rent_amount ?? 0),
                                    props.app.locale,
                                    lease?.currency ?? 'SAR',
                                )}
                            </strong>
                        </div>
                    </div>
                    <div className="pmc-document-list mt-4">
                        {documents.length > 0 ? (
                            documents.map((document) => (
                                <div key={document.id}>
                                    <i className="bi bi-file-earmark-text" />
                                    <strong>{document.title_en}</strong>
                                    <span>{document.type}</span>
                                </div>
                            ))
                        ) : (
                            <InlineEmptyState message="No contract documents are available yet." />
                        )}
                    </div>
                </section>

                <section className="pmc-card p-4 pmc-dashboard-span-6">
                    <SectionTitle
                        eyebrow="Payments"
                        title="Rent payment history"
                        actionHref="/payments"
                        actionLabel="Open payments"
                    />
                    <ActivityTable
                        empty="No posted payments yet."
                        rows={payments.map((payment) => ({
                            id: payment.id,
                            title:
                                payment.reference ?? `Receipt #${payment.id}`,
                            meta: humanDate(
                                payment.received_on,
                                props.app.locale,
                            ),
                            value: currency(
                                payment.amount,
                                props.app.locale,
                                payment.currency,
                            ),
                        }))}
                    />
                </section>

                <section className="pmc-card p-4 pmc-dashboard-span-12">
                    <SectionTitle
                        eyebrow="Maintenance"
                        title="Your submitted requests"
                        actionHref="/maintenance-requests"
                        actionLabel="Submit request"
                    />
                    <ActivityTable
                        empty="No maintenance requests submitted yet."
                        rows={requests.map((request) => ({
                            id: request.id,
                            title: request.title,
                            meta: humanDate(
                                request.created_at,
                                props.app.locale,
                            ),
                            value: request.status,
                        }))}
                    />
                </section>
            </div>
        </AdminLayout>
    );
}

function SectionTitle({
    eyebrow,
    title,
    actionHref,
    actionLabel,
}: {
    eyebrow: string;
    title: string;
    actionHref?: string;
    actionLabel?: string;
}) {
    return (
        <div className="pmc-section-title">
            <div>
                <div className="pmc-kicker mb-2">{eyebrow}</div>
                <h2>{title}</h2>
            </div>
            {actionHref && actionLabel ? (
                <Link
                    href={actionHref}
                    className="btn btn-outline-secondary btn-sm"
                >
                    {actionLabel}
                </Link>
            ) : null}
        </div>
    );
}

function LeaseList({
    leases,
    locale,
    empty,
    showBalanceOnly = false,
}: {
    leases: LeaseBalance[];
    locale: 'en' | 'ar';
    empty: string;
    showBalanceOnly?: boolean;
}) {
    if (leases.length === 0) {
        return <InlineEmptyState message={empty} />;
    }

    return (
        <div className="pmc-lease-list">
            {leases.map((lease) => (
                <Link key={lease.id} href="/leases">
                    <div>
                        <strong>{lease.code}</strong>
                        <span>
                            {lease.tenant ?? 'No tenant'} ·{' '}
                            {lease.asset ?? 'No asset'}
                        </span>
                    </div>
                    <em>
                        {showBalanceOnly
                            ? currency(
                                  lease.balance_remaining,
                                  locale,
                                  lease.currency,
                              )
                            : `${lease.days_remaining ?? 0} days`}
                    </em>
                </Link>
            ))}
        </div>
    );
}

function MiniMetricList({
    source,
    empty,
}: {
    source: Record<string, number>;
    empty: string;
}) {
    const entries = Object.entries(source);

    if (entries.length === 0) {
        return <InlineEmptyState message={empty} />;
    }

    return (
        <div className="pmc-mini-metric-list">
            {entries.map(([label, value]) => (
                <div key={label}>
                    <span>{label.replaceAll('_', ' ')}</span>
                    <strong>{value}</strong>
                </div>
            ))}
        </div>
    );
}

function ActivityTable({
    rows,
    empty,
}: {
    rows: Array<{ id: number; title: string; meta: string; value: string }>;
    empty: string;
}) {
    if (rows.length === 0) {
        return <InlineEmptyState message={empty} />;
    }

    return (
        <div className="pmc-activity-list">
            {rows.map((row) => (
                <div key={row.id}>
                    <div>
                        <strong>{row.title}</strong>
                        <span>{row.meta}</span>
                    </div>
                    <em>{row.value}</em>
                </div>
            ))}
        </div>
    );
}

function ChartEmptyState({
    icon,
    title,
    message,
}: {
    icon: string;
    title: string;
    message: string;
}) {
    return (
        <div className="pmc-chart-empty">
            <i className={`bi ${icon}`} />
            <strong>{title}</strong>
            <span>{message}</span>
        </div>
    );
}

function InlineEmptyState({ message }: { message: string }) {
    return <div className="pmc-inline-empty">{message}</div>;
}

const chartColors = ['#ef6c2f', '#0c8a7c', '#ffca4b', '#24314a', '#38bdf8'];
