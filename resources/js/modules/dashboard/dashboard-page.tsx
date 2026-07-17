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

import { StatCard } from '@/components/stat-card';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency, humanDate } from '@/lib/utils';

import { operationsCycleSteps, operationsHealthScore } from './metrics';
import type { DashboardPageProps } from './types';
import {
    ActivityTable,
    ChartEmptyState,
    CycleMap,
    InlineEmptyState,
    LeaseList,
    MiniMetricList,
    NextActionDeck,
    PropertyMap,
    SectionTitle,
    chartColors,
} from './widgets';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    ArcElement,
    Tooltip,
    Legend,
);

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
    const nextActions = props.nextActions ?? [];
    const propertyMapAssets = props.propertyMap?.assets ?? [];
    const healthScore = operationsHealthScore(setupChecklist, props.stats);
    const cycleSteps = operationsCycleSteps(setupChecklist, props.stats);

    return (
        <AdminLayout>
            <Head title="Dashboard" />

            <section className="pmc-dashboard-brief">
                <div>
                    <div className="pmc-kicker mb-3">
                        {props.mode === 'superadmin'
                            ? 'Platform command'
                            : 'Portfolio command'}
                    </div>
                    <h1>
                        {props.mode === 'superadmin'
                            ? 'Control every portfolio without opening ten tabs.'
                            : 'Run the portfolio from asset setup to rent collection.'}
                    </h1>
                    <p>
                        This is the operating brief: setup progress, rent
                        health, service pressure, expiring contracts, and the
                        next page to open.
                    </p>
                    <div className="pmc-dashboard-context">
                        {props.mode === 'superadmin' ? (
                            <>
                                <span>
                                    <i className="bi bi-buildings" />
                                    {props.stats.totalPortfolios ?? 0}{' '}
                                    portfolios
                                </span>
                                <span>
                                    <i className="bi bi-people" />
                                    {props.stats.totalUsers ?? 0} users
                                </span>
                            </>
                        ) : null}
                        <span>
                            <i className="bi bi-file-earmark-text" />
                            {props.stats.activeLeases ?? 0} active leases
                        </span>
                        <span>
                            <i className="bi bi-tools" />
                            {props.stats.openRequests ?? 0} open issues
                        </span>
                    </div>
                </div>

                <div className="pmc-dashboard-health">
                    <div>
                        <span>{healthScore}</span>
                        <small>Operating score</small>
                    </div>
                    <p>
                        {healthScore >= 80
                            ? 'Healthy. Keep watching renewals, payments, and service backlog.'
                            : healthScore >= 45
                              ? 'Usable, but setup or collections still need attention.'
                              : 'Not production-ready yet. Build the cycle before relying on reports.'}
                    </p>
                    <div className="pmc-command-actions">
                        <Link href="/assets/create" className="btn btn-primary">
                            <i className="bi bi-plus-lg me-2" />
                            Create asset
                        </Link>
                        <Link href="/reports" className="btn btn-light">
                            <i className="bi bi-bar-chart-line me-2" />
                            Reports
                        </Link>
                        <Link href="/documentation" className="btn btn-light">
                            <i className="bi bi-journal-richtext me-2" />
                            Open docs
                        </Link>
                    </div>
                </div>
            </section>

            <NextActionDeck actions={nextActions} />

            <PropertyMap
                assets={propertyMapAssets}
                locale={props.app.locale}
                summary={props.propertyMap?.summary}
            />

            <CycleMap steps={cycleSteps} />

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
    const nextActions = props.nextActions ?? [];
    const paidAmount = Number(props.stats.paidAmount ?? 0);
    const amountLeft = Number(props.stats.amountLeft ?? 0);
    const paymentBase = paidAmount + amountLeft;
    const paymentProgress =
        paymentBase > 0 ? Math.round((paidAmount / paymentBase) * 100) : 0;

    return (
        <AdminLayout>
            <Head title="Tenant Dashboard" />

            <section className="pmc-tenant-command">
                <div>
                    <div className="pmc-kicker mb-3">Tenant portal</div>
                    <h1>{lease?.leaseable?.title_en ?? 'No active lease'}</h1>
                    <p>
                        {lease
                            ? `${lease.code} · ${lease.leaseable?.code ?? 'Asset'}`
                            : 'Ask your property owner to create your lease and portal access before rent, documents, and service requests appear.'}
                    </p>
                    <div className="pmc-dashboard-context">
                        <span>
                            <i className="bi bi-calendar-check" />
                            {props.stats.daysLeft ?? 0} days left
                        </span>
                        <span>
                            <i className="bi bi-receipt" />
                            {payments.length} payments
                        </span>
                        <span>
                            <i className="bi bi-folder2-open" />
                            {documents.length} documents
                        </span>
                    </div>
                </div>

                <div className="pmc-tenant-payment-card">
                    <span>Payment progress</span>
                    <strong>{paymentProgress}%</strong>
                    <div className="pmc-tenant-progress">
                        <i style={{ width: `${paymentProgress}%` }} />
                    </div>
                    <small>
                        {currency(
                            paidAmount,
                            props.app.locale,
                            lease?.currency ?? 'SAR',
                        )}{' '}
                        paid ·{' '}
                        {currency(
                            amountLeft,
                            props.app.locale,
                            lease?.currency ?? 'SAR',
                        )}{' '}
                        left
                    </small>
                    <Link
                        href="/maintenance-requests"
                        className="btn btn-primary mt-3"
                    >
                        <i className="bi bi-tools me-2" />
                        Request maintenance
                    </Link>
                </div>
            </section>

            <NextActionDeck actions={nextActions} />

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
                    {lease ? (
                        <div className="d-flex gap-2 mt-4 flex-wrap">
                            <a
                                href={lease.contract_url}
                                className="btn btn-outline-secondary btn-sm"
                            >
                                <i className="bi bi-file-earmark-text me-2" />
                                Download contract
                            </a>
                            <a
                                href={lease.statement_url}
                                className="btn btn-outline-secondary btn-sm"
                            >
                                <i className="bi bi-receipt me-2" />
                                Tenant statement
                            </a>
                        </div>
                    ) : null}
                    <div className="pmc-document-list mt-4">
                        {documents.length > 0 ? (
                            documents.map((document) => (
                                <a
                                    key={document.id}
                                    href={document.download_url}
                                >
                                    <i className="bi bi-file-earmark-text" />
                                    <strong>{document.title_en}</strong>
                                    <span>{document.type}</span>
                                </a>
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
                    />
                    {payments.length > 0 ? (
                        <div className="pmc-activity-list">
                            {payments.map((payment) => (
                                <a key={payment.id} href={payment.receipt_url}>
                                    <div>
                                        <strong>
                                            {payment.reference ??
                                                `Receipt #${payment.id}`}
                                        </strong>
                                        <span>
                                            {humanDate(
                                                payment.received_on,
                                                props.app.locale,
                                            )}
                                        </span>
                                    </div>
                                    <em>
                                        {currency(
                                            payment.amount,
                                            props.app.locale,
                                            payment.currency,
                                        )}
                                    </em>
                                </a>
                            ))}
                        </div>
                    ) : (
                        <InlineEmptyState message="No posted payments yet." />
                    )}
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
