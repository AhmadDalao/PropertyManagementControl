import { Head, usePage } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import {
    MetricGrid,
    RecordActions,
    StatusBadge,
    WorkspaceHeader,
    humanLabel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type RequestRecord = {
    id: number;
    title: string;
    status: string;
    category: string;
    priority: string;
    created_at: string;
    due_at?: string | null;
    is_overdue: boolean;
    assigned_to?: { id: number; name: string } | null;
    asset?: {
        id: number;
        title_en: string;
        title_ar?: string | null;
        code?: string | null;
    } | null;
    tenant_profile?: {
        user?: { name?: string | null; email?: string | null };
    };
    expense_total: number;
    expense_count: number;
};

type PageProps = SharedProps & {
    mode: 'tenant' | 'manager';
    requests: PaginatedData<RequestRecord>;
    maintenanceInsights: {
        total: number;
        open: number;
        in_progress: number;
        resolved: number;
        cancelled: number;
        urgent: number;
        overdue: number;
        unassigned: number;
        posted_expenses: number;
    };
    filters: TableFilters;
    counts: TableCount[];
    categoryOptions: string[];
    priorityOptions: string[];
    statusOptions: string[];
};

export default function MaintenanceIndexPage() {
    const { props } = usePage<PageProps>();
    const { locale, t, text } = useTranslator();
    const activeCount =
        props.maintenanceInsights.open + props.maintenanceInsights.in_progress;
    const filterFields: TableFilterField[] = [
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
        {
            name: 'category',
            label: 'Category',
            options: [
                { label: 'All', value: 'all' },
                ...props.categoryOptions.map((category) => ({
                    label: humanLabel(category),
                    value: category,
                })),
            ],
        },
        {
            name: 'priority',
            label: 'Priority',
            options: [
                { label: 'All', value: 'all' },
                ...props.priorityOptions.map((priority) => ({
                    label: humanLabel(priority),
                    value: priority,
                })),
            ],
        },
        { name: 'date_from', label: 'From', type: 'date' },
        { name: 'date_to', label: 'To', type: 'date' },
    ];

    return (
        <AdminLayout>
            <Head title={text('Maintenance')} />

            <WorkspaceHeader
                eyebrow="Money & service"
                title="Maintenance"
                description={
                    props.mode === 'tenant'
                        ? 'Submit a property issue, then open the request to follow owner and manager updates.'
                        : 'Find a request and open it to assign work, update the tenant, record cost, resolve, or reopen.'
                }
                actions={[
                    ...(props.mode === 'manager'
                        ? [
                              {
                                  label: 'Expenses',
                                  href: '/expenses',
                                  icon: 'bi-receipt',
                                  tone: 'quiet' as const,
                              },
                          ]
                        : []),
                    {
                        label: 'Create request',
                        href: '/maintenance-requests/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Active requests',
                        value: activeCount,
                        detail: t('maintenance.active_mix', undefined, {
                            open: props.maintenanceInsights.open,
                            in_progress: props.maintenanceInsights.in_progress,
                        }),
                        icon: 'bi-tools',
                        tone: 'ink',
                    },
                    {
                        label: 'Urgent',
                        value: props.maintenanceInsights.urgent,
                        detail:
                            props.mode === 'manager'
                                ? t('maintenance.unassigned', undefined, {
                                      count: props.maintenanceInsights
                                          .unassigned,
                                  })
                                : 'High-priority tenant issues',
                        icon: 'bi-exclamation-triangle',
                        tone:
                            props.maintenanceInsights.urgent > 0
                                ? 'red'
                                : 'teal',
                    },
                    {
                        label: 'Overdue',
                        value: props.maintenanceInsights.overdue,
                        detail: t('maintenance.resolved', undefined, {
                            count: props.maintenanceInsights.resolved,
                        }),
                        icon: 'bi-clock-history',
                        tone:
                            props.maintenanceInsights.overdue > 0
                                ? 'amber'
                                : 'blue',
                    },
                    {
                        label:
                            props.mode === 'manager'
                                ? 'Posted service cost'
                                : 'Request history',
                        value:
                            props.mode === 'manager'
                                ? currency(
                                      props.maintenanceInsights.posted_expenses,
                                      props.app.locale,
                                  )
                                : props.maintenanceInsights.total,
                        detail: t('maintenance.total_requests', undefined, {
                            count: props.maintenanceInsights.total,
                        }),
                        icon:
                            props.mode === 'manager'
                                ? 'bi-cash-coin'
                                : 'bi-clock-history',
                        tone: 'teal',
                    },
                ]}
            />

            <DataTable
                title="Maintenance queue"
                description="Search request, category, asset, tenant, or assignee."
                data={props.requests}
                filters={props.filters}
                counts={props.counts}
                basePath="/maintenance-requests"
                rowHref={(request) => `/maintenance-requests/${request.id}`}
                exportHref={
                    props.mode === 'manager'
                        ? exportUrl(
                              '/exports/maintenance-requests',
                              props.filters,
                          )
                        : undefined
                }
                filterFields={filterFields}
                columns={[
                    {
                        key: 'request',
                        label: 'Request',
                        render: (request) => (
                            <div className="pmc-primary-cell">
                                <strong>
                                    #{request.id} {request.title}
                                </strong>
                                <span>
                                    {text(humanLabel(request.category))}
                                </span>
                                {request.is_overdue ? (
                                    <StatusBadge
                                        value="overdue"
                                        tone="danger"
                                    />
                                ) : null}
                            </div>
                        ),
                    },
                    {
                        key: 'asset',
                        label: 'Asset / tenant',
                        render: (request) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {(locale === 'ar'
                                        ? request.asset?.title_ar ||
                                          request.asset?.title_en
                                        : request.asset?.title_en ||
                                          request.asset?.title_ar) ??
                                        text('No asset')}
                                </strong>
                                <span>
                                    {request.tenant_profile?.user?.name ??
                                        text('No tenant')}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'assignment',
                        label: 'Assignment',
                        render: (request) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {request.assigned_to?.name ??
                                        text('Unassigned')}
                                </strong>
                                <span>
                                    {currency(
                                        request.expense_total,
                                        props.app.locale,
                                    )}{' '}
                                    {t('maintenance.cost_entries', undefined, {
                                        count: request.expense_count,
                                    })}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'priority',
                        label: 'Priority',
                        render: (request) => (
                            <StatusBadge
                                value={request.priority}
                                tone={
                                    request.priority === 'urgent'
                                        ? 'danger'
                                        : request.priority === 'high'
                                          ? 'warning'
                                          : 'neutral'
                                }
                            />
                        ),
                    },
                    {
                        key: 'status',
                        label: 'Status',
                        render: (request) => (
                            <div className="pmc-stacked-cell">
                                <StatusBadge value={request.status} />
                                <span>
                                    {text('Due')}{' '}
                                    {humanDate(
                                        request.due_at,
                                        props.app.locale,
                                    )}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'created',
                        label: 'Created',
                        render: (request) =>
                            humanDate(request.created_at, props.app.locale),
                    },
                    {
                        key: 'actions',
                        label: 'Actions',
                        className: 'text-end',
                        render: (request) => (
                            <RecordActions
                                showHref={`/maintenance-requests/${request.id}`}
                                editHref={
                                    props.mode === 'manager'
                                        ? `/maintenance-requests/${request.id}/edit`
                                        : undefined
                                }
                            >
                                {!['cancelled', 'resolved'].includes(
                                    request.status,
                                ) ? (
                                    <ArchiveAction
                                        href={`/maintenance-requests/${request.id}`}
                                        label="Cancel"
                                        confirmMessage={t(
                                            'maintenance.cancel_confirm',
                                            undefined,
                                            { id: request.id },
                                        )}
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
