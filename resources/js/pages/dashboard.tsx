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
import { currency, humanDate } from '@/lib/utils';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend);

type DashboardPageProps = SharedProps & {
    mode: 'tenant' | 'portfolio' | 'superadmin';
    stats: Record<string, number | string | null>;
    charts?: {
        occupancy?: Record<string, number>;
        paymentHealth?: Array<{
            code: string;
            tenant: string;
            due: number;
            paid: number;
            remaining: number;
        }>;
    };
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
        created_at: string;
        asset?: { title_en: string };
    }>;
    tenantPortal?: {
        lease?: {
            code: string;
            days_remaining: number;
            balance_remaining: number;
            documents?: Array<{ id: number; title_en: string }>;
        } | null;
        payments?: Array<{
            id: number;
            amount: number;
            currency: string;
            received_on: string;
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

    const occupancySource = props.charts?.occupancy ?? {};
    const occupancyChart = {
        labels: Object.keys(occupancySource),
        datasets: [
            {
                data: Object.values(occupancySource),
                backgroundColor: ['#ef6c2f', '#0c8a7c', '#ffca4b', '#24314a'],
            },
        ],
    };

    const paymentHealth = props.charts?.paymentHealth ?? [];
    const paymentChart = {
        labels: paymentHealth.map((item) => item.code),
        datasets: [
            {
                label: 'Paid',
                data: paymentHealth.map((item) => item.paid),
                backgroundColor: '#0c8a7c',
            },
            {
                label: 'Remaining',
                data: paymentHealth.map((item) => item.remaining),
                backgroundColor: '#ef6c2f',
            },
        ],
    };

    return (
        <AdminLayout>
            <Head title="Dashboard" />

            <PageHeader
                title="Dashboard"
                description="A live operational snapshot of assets, revenue, tenants, and maintenance."
            />

            <div className="row g-3 mb-4">
                {props.mode === 'tenant' ? (
                    <>
                        <div className="col-md-3">
                            <StatCard title="Lease" value={props.stats.leaseCode} tone="accent" />
                        </div>
                        <div className="col-md-3">
                            <StatCard title="Days left" value={props.stats.daysLeft} />
                        </div>
                        <div className="col-md-3">
                            <StatCard
                                title="Amount left"
                                value={currency(Number(props.stats.amountLeft ?? 0), props.app.locale)}
                                tone="teal"
                            />
                        </div>
                        <div className="col-md-3">
                            <StatCard
                                title="Paid"
                                value={currency(Number(props.stats.paidAmount ?? 0), props.app.locale)}
                            />
                        </div>
                    </>
                ) : (
                    <>
                        <div className="col-md-3">
                            <StatCard title="Users" value={props.stats.totalUsers} tone="accent" />
                        </div>
                        <div className="col-md-3">
                            <StatCard title="Assets" value={props.stats.totalAssets} />
                        </div>
                        <div className="col-md-3">
                            <StatCard
                                title="Value"
                                value={currency(Number(props.stats.totalValue ?? 0), props.app.locale)}
                                tone="teal"
                            />
                        </div>
                        <div className="col-md-3">
                            <StatCard
                                title="Monthly revenue"
                                value={currency(Number(props.stats.monthlyRevenue ?? 0), props.app.locale)}
                            />
                        </div>
                    </>
                )}
            </div>

            {props.mode === 'tenant' ? (
                <div className="row g-4">
                    <div className="col-lg-7">
                        <div className="pmc-card p-4">
                            <div className="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div className="pmc-kicker mb-2">Lease documents</div>
                                    <h2 className="h4 mb-0">Contract and history</h2>
                                </div>
                                <Link href="/maintenance-requests" className="btn btn-outline-secondary btn-sm">
                                    Maintenance
                                </Link>
                            </div>

                            <div className="table-responsive">
                                <table className="table pmc-table">
                                    <thead>
                                        <tr>
                                            <th>Payment</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {props.tenantPortal?.payments?.map((payment) => (
                                            <tr key={payment.id}>
                                                <td>Receipt #{payment.id}</td>
                                                <td>{humanDate(payment.received_on, props.app.locale)}</td>
                                                <td>{currency(payment.amount, props.app.locale, payment.currency)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div className="col-lg-5">
                        <div className="pmc-card p-4 h-100">
                            <div className="pmc-kicker mb-2">Maintenance log</div>
                            <h2 className="h4 mb-4">Latest tenant requests</h2>
                            <div className="d-grid gap-3">
                                {props.tenantPortal?.requests?.map((item) => (
                                    <div key={item.id} className="border rounded-4 p-3">
                                        <div className="d-flex justify-content-between gap-3 mb-2">
                                            <strong>{item.title}</strong>
                                            <span className="pmc-chip pmc-chip--primary">{item.status}</span>
                                        </div>
                                        <div className="small text-secondary">
                                            {humanDate(item.created_at, props.app.locale)}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            ) : (
                <div className="row g-4">
                    <div className="col-lg-5">
                        <div className="pmc-card p-4 h-100">
                            <div className="pmc-kicker mb-2">Occupancy</div>
                            <h2 className="h4 mb-4">Asset status mix</h2>
                            <Doughnut data={occupancyChart} />
                        </div>
                    </div>
                    <div className="col-lg-7">
                        <div className="pmc-card p-4 h-100">
                            <div className="pmc-kicker mb-2">Payment health</div>
                            <h2 className="h4 mb-4">Lease balances</h2>
                            <Bar data={paymentChart} />
                        </div>
                    </div>

                    <div className="col-lg-6">
                        <div className="pmc-card p-4">
                            <div className="pmc-kicker mb-2">Recent payments</div>
                            <div className="table-responsive">
                                <table className="table pmc-table">
                                    <thead>
                                        <tr>
                                            <th>Tenant</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {props.recentPayments?.map((payment) => (
                                            <tr key={payment.id}>
                                                <td>{payment.tenant_profile?.user?.name ?? '-'}</td>
                                                <td>{humanDate(payment.received_on, props.app.locale)}</td>
                                                <td>{currency(payment.amount, props.app.locale, payment.currency)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div className="col-lg-6">
                        <div className="pmc-card p-4">
                            <div className="pmc-kicker mb-2">Maintenance queue</div>
                            <div className="table-responsive">
                                <table className="table pmc-table">
                                    <thead>
                                        <tr>
                                            <th>Request</th>
                                            <th>Asset</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {props.recentMaintenance?.map((item) => (
                                            <tr key={item.id}>
                                                <td>{item.title}</td>
                                                <td>{item.asset?.title_en ?? '-'}</td>
                                                <td>
                                                    <span className="pmc-chip pmc-chip--primary">
                                                        {item.status}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
