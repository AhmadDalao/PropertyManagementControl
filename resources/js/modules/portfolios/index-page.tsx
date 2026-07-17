import { Head, usePage } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import {
    MetricGrid,
    RecordActions,
    StatusBadge,
    WorkspaceHeader,
    humanLabel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency } from '@/lib/utils';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type PortfolioRecord = {
    id: number;
    name_en: string;
    name_ar: string;
    code: string;
    status: string;
    city?: string | null;
    country?: string | null;
    default_currency?: string | null;
    users_count?: number;
    assets_count?: number;
    leases_count?: number;
    active_leases_count?: number;
    open_maintenance_count?: number;
    valuation_total?: number | null;
    posted_revenue_total?: number | null;
    module_settings?: Record<string, boolean> | null;
};

type ModuleDefinition = {
    key: string;
    label: string;
    description: string;
};

type PortfolioInsights = {
    total: number;
    active: number;
    archived: number;
    assets: number;
    users: number;
    leases: number;
    active_leases: number;
    open_maintenance: number;
    valuation_total: number;
    posted_revenue_total: number;
};

type PageProps = SharedProps & {
    portfolios: PaginatedData<PortfolioRecord>;
    portfolioInsights: PortfolioInsights;
    filters: TableFilters;
    counts: TableCount[];
    canCreate: boolean;
    canUpdate: boolean;
    moduleDefinitions: ModuleDefinition[];
    statusOptions: string[];
};

export default function PortfoliosIndexPage() {
    const { props } = usePage<PageProps>();

    return (
        <AdminLayout>
            <Head title="Portfolios" />

            <WorkspaceHeader
                eyebrow="Portfolio"
                title="Portfolios"
                description="Each portfolio is a client boundary for users, properties, leases, money, documents, and module access."
                actions={
                    props.canCreate
                        ? [
                              {
                                  label: 'Create portfolio',
                                  href: '/portfolios/create',
                                  icon: 'bi-plus-lg',
                                  tone: 'primary',
                              },
                          ]
                        : []
                }
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Portfolios',
                        value: props.portfolioInsights.total,
                        detail: `${props.portfolioInsights.active} active · ${props.portfolioInsights.archived} archived`,
                        icon: 'bi-buildings',
                        tone: 'ink',
                    },
                    {
                        label: 'Managed value',
                        value: currency(
                            props.portfolioInsights.valuation_total,
                            props.app.locale,
                        ),
                        detail: `${props.portfolioInsights.assets} property records`,
                        icon: 'bi-bank',
                        tone: 'blue',
                    },
                    {
                        label: 'Posted revenue',
                        value: currency(
                            props.portfolioInsights.posted_revenue_total,
                            props.app.locale,
                        ),
                        detail: `${props.portfolioInsights.active_leases} active leases`,
                        icon: 'bi-cash-stack',
                        tone: 'teal',
                    },
                    {
                        label: 'Open service',
                        value: props.portfolioInsights.open_maintenance,
                        detail: `${props.portfolioInsights.users} users in scope`,
                        icon: 'bi-tools',
                        tone:
                            props.portfolioInsights.open_maintenance > 0
                                ? 'amber'
                                : 'teal',
                    },
                ]}
            />

            <DataTable
                title="Portfolio directory"
                description="Search client name, code, contact, city, or country."
                data={props.portfolios}
                filters={props.filters}
                counts={props.counts}
                basePath="/portfolios"
                rowHref={(portfolio) => `/portfolios/${portfolio.id}`}
                exportHref={exportUrl('/exports/portfolios', props.filters)}
                filterFields={[
                    {
                        name: 'status',
                        label: 'Status',
                        options: [
                            { label: 'All', value: 'all' },
                            ...props.statusOptions.map((status) => ({
                                label: humanLabel(status),
                                value: status,
                            })),
                        ],
                    },
                ]}
                columns={[
                    {
                        key: 'portfolio',
                        label: 'Portfolio',
                        render: (portfolio) => (
                            <div className="pmc-primary-cell">
                                <strong>{portfolio.name_en}</strong>
                                <span>
                                    {portfolio.code} · {portfolio.name_ar}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'location',
                        label: 'Location',
                        render: (portfolio) => (
                            <div className="pmc-stacked-cell">
                                <strong>{portfolio.city ?? 'Not set'}</strong>
                                <span>{portfolio.country ?? 'Not set'}</span>
                            </div>
                        ),
                    },
                    {
                        key: 'activity',
                        label: 'Activity',
                        render: (portfolio) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {portfolio.assets_count ?? 0} assets ·{' '}
                                    {portfolio.users_count ?? 0} users
                                </strong>
                                <span>
                                    {portfolio.active_leases_count ?? 0} active
                                    leases ·{' '}
                                    {portfolio.open_maintenance_count ?? 0}{' '}
                                    service
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'modules',
                        label: 'Modules',
                        render: (portfolio) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {enabledModuleCount(
                                        props.moduleDefinitions,
                                        portfolio.module_settings,
                                    )}{' '}
                                    enabled
                                </strong>
                                <span>
                                    of {props.moduleDefinitions.length}{' '}
                                    available
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'value',
                        label: 'Value / revenue',
                        render: (portfolio) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {currency(
                                        portfolio.valuation_total ?? 0,
                                        props.app.locale,
                                        portfolio.default_currency ?? 'SAR',
                                    )}
                                </strong>
                                <span>
                                    {currency(
                                        portfolio.posted_revenue_total ?? 0,
                                        props.app.locale,
                                        portfolio.default_currency ?? 'SAR',
                                    )}{' '}
                                    revenue
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'status',
                        label: 'Status',
                        render: (portfolio) => (
                            <StatusBadge value={portfolio.status} />
                        ),
                    },
                    {
                        key: 'actions',
                        label: 'Actions',
                        className: 'text-end',
                        render: (portfolio) => (
                            <RecordActions
                                showHref={`/portfolios/${portfolio.id}`}
                                editHref={
                                    props.canUpdate
                                        ? `/portfolios/${portfolio.id}/edit`
                                        : undefined
                                }
                            >
                                {props.canCreate &&
                                portfolio.status !== 'archived' ? (
                                    <ArchiveAction
                                        href={`/portfolios/${portfolio.id}`}
                                        confirmMessage={`Archive portfolio ${portfolio.name_en}? Users and records stay available for reporting.`}
                                    />
                                ) : null}
                            </RecordActions>
                        ),
                    },
                ]}
            />
        </AdminLayout>
    );
}

function enabledModuleCount(
    definitions: ModuleDefinition[],
    settings?: Record<string, boolean> | null,
) {
    return definitions.filter(
        (definition) => settings?.[definition.key] ?? true,
    ).length;
}
