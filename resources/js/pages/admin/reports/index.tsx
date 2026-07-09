import { Head, usePage } from '@inertiajs/react';
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
import { currency, percent } from '@/lib/utils';
import type { SharedProps } from '@/types';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    ArcElement,
    Tooltip,
    Legend,
);

type PageProps = SharedProps & {
    mode: 'tenant' | 'portfolio' | 'superadmin';
    summary: {
        revenue?: number;
        expenses?: number;
        net?: number;
        occupancyRate?: number;
        arrears?: number;
    };
    charts: {
        revenueByMonth?: Record<string, number>;
        expenseByCategory?: Record<string, number>;
        assetMix?: Record<string, number>;
    };
};

export default function ReportsPage() {
    const { props } = usePage<PageProps>();

    const revenueSource = props.charts.revenueByMonth ?? {};
    const expenseSource = props.charts.expenseByCategory ?? {};
    const assetMixSource = props.charts.assetMix ?? {};

    return (
        <AdminLayout>
            <Head title="Reports" />
            <PageHeader
                title="Reports"
                description="Visualize revenue, expenses, occupancy, and arrears across the platform."
            />

            <div className="row g-3 mb-4">
                <div className="col-md-3">
                    <StatCard
                        title="Revenue"
                        value={currency(
                            props.summary.revenue ?? 0,
                            props.app.locale,
                        )}
                        tone="accent"
                    />
                </div>
                <div className="col-md-3">
                    <StatCard
                        title="Expenses"
                        value={currency(
                            props.summary.expenses ?? 0,
                            props.app.locale,
                        )}
                    />
                </div>
                <div className="col-md-3">
                    <StatCard
                        title="Net"
                        value={currency(
                            props.summary.net ?? 0,
                            props.app.locale,
                        )}
                        tone="teal"
                    />
                </div>
                <div className="col-md-3">
                    <StatCard
                        title="Occupancy"
                        value={percent(props.summary.occupancyRate ?? 0)}
                    />
                </div>
            </div>

            <div className="row g-4">
                <div className="col-lg-7">
                    <div className="pmc-card p-4 h-100">
                        <div className="pmc-kicker mb-2">Revenue by month</div>
                        <Bar
                            data={{
                                labels: Object.keys(revenueSource),
                                datasets: [
                                    {
                                        label: 'Revenue',
                                        data: Object.values(revenueSource),
                                        backgroundColor: '#ef6c2f',
                                    },
                                ],
                            }}
                        />
                    </div>
                </div>

                <div className="col-lg-5">
                    <div className="pmc-card p-4 h-100">
                        <div className="pmc-kicker mb-2">Asset mix</div>
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
                                        ],
                                    },
                                ],
                            }}
                        />
                    </div>
                </div>

                <div className="col-12">
                    <div className="pmc-card p-4">
                        <div className="pmc-kicker mb-2">
                            Expense categories
                        </div>
                        <div className="row g-3">
                            {Object.entries(expenseSource).map(
                                ([label, value]) => (
                                    <div key={label} className="col-md-4">
                                        <div className="rounded-4 p-3 border">
                                            <div className="pmc-kicker mb-2">
                                                {label}
                                            </div>
                                            <div className="h4 mb-0">
                                                {currency(
                                                    value,
                                                    props.app.locale,
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ),
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
