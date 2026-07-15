import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { AdminLayout } from '@/layouts/admin-layout';
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
    description: string;
    status: string;
    category: string;
    priority: string;
    created_at: string;
    requested_at?: string | null;
    due_at?: string | null;
    resolved_at?: string | null;
    is_overdue: boolean;
    assigned_to_user_id?: number | null;
    assigned_to?: { id: number; name: string } | null;
    internal_notes?: string | null;
    asset?: { id: number; title_en: string; code?: string | null } | null;
    tenant_profile?: {
        id?: number | null;
        user?: { name?: string | null; email?: string | null };
    };
    expense_total: number;
    expense_count: number;
    updates: MaintenanceUpdateRecord[];
};

type MaintenanceUpdateRecord = {
    id: number;
    user?: string | null;
    status_from?: string | null;
    status_to?: string | null;
    is_public_comment: boolean;
    comment?: string | null;
    created_at?: string | null;
};

type MaintenanceInsights = {
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

type PageProps = SharedProps & {
    mode: 'tenant' | 'manager';
    requests: PaginatedData<RequestRecord>;
    maintenanceInsights: MaintenanceInsights;
    filters: TableFilters;
    counts: TableCount[];
    categoryOptions: string[];
    priorityOptions: string[];
    statusOptions: string[];
    assetOptions: Array<{ id: number; title_en: string; code?: string | null }>;
    tenantProfile?: { id: number };
    tenantOptions?: Array<{ id: number; user?: { name: string } }>;
    userOptions?: Array<{ id: number; name: string }>;
};

export default function MaintenancePage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<RequestRecord | null>(null);
    const [selectedRequestId, setSelectedRequestId] = useState<number | null>(
        null,
    );

    const tenantForm = useForm({
        asset_id: String(props.assetOptions[0]?.id ?? ''),
        category: 'electricity',
        priority: 'medium',
        title: '',
        description: '',
    });

    const managerForm = useForm({
        asset_id: String(props.assetOptions[0]?.id ?? ''),
        tenant_profile_id: String(props.tenantOptions?.[0]?.id ?? ''),
        assigned_to_user_id: '',
        category: 'plumbing',
        priority: 'medium',
        status: 'open',
        title: '',
        description: '',
        internal_notes: '',
        comment: '',
        is_public_comment: false,
    });

    const commentForm = useForm({
        comment: '',
    });

    const startTriage = (requestItem: RequestRecord) => {
        managerForm.setData({
            asset_id: '',
            tenant_profile_id: '',
            assigned_to_user_id: requestItem.assigned_to_user_id
                ? String(requestItem.assigned_to_user_id)
                : '',
            category: requestItem.category,
            priority: requestItem.priority,
            status: requestItem.status,
            title: requestItem.title,
            description: '',
            internal_notes: requestItem.internal_notes ?? '',
            comment: '',
            is_public_comment: false,
        });
        setEditing(requestItem);
        setSelectedRequestId(requestItem.id);
    };

    const clearTriage = () => {
        setEditing(null);
        managerForm.reset();
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (props.mode === 'tenant') {
            tenantForm.post('/maintenance-requests', { preserveScroll: true });

            return;
        }

        if (editing) {
            managerForm.put(`/maintenance-requests/${editing.id}`, {
                preserveScroll: true,
                onSuccess: clearTriage,
            });

            return;
        }

        managerForm.post('/maintenance-requests', { preserveScroll: true });
    };

    const selectedRequest =
        props.requests.data.find(
            (request) => request.id === selectedRequestId,
        ) ??
        props.requests.data[0] ??
        null;

    const submitTenantComment = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!selectedRequest || props.mode !== 'tenant') {
            return;
        }

        commentForm.put(`/maintenance-requests/${selectedRequest.id}`, {
            preserveScroll: true,
            onSuccess: () => commentForm.reset(),
        });
    };

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

    const activeErrors =
        props.mode === 'tenant' ? tenantForm.errors : managerForm.errors;
    const activeRequestCount =
        props.maintenanceInsights.open + props.maintenanceInsights.in_progress;
    const cannotSubmit =
        props.mode === 'tenant'
            ? props.assetOptions.length === 0
            : !editing &&
              (props.assetOptions.length === 0 ||
                  (props.tenantOptions?.length ?? 0) === 0);

    return (
        <AdminLayout>
            <Head title="Maintenance" />

            <section className="pmc-maintenance-command mb-4">
                <div>
                    <span className="pmc-kicker">Service control</span>
                    <h1>
                        {props.mode === 'tenant'
                            ? 'Request maintenance and track every owner update.'
                            : 'Triage tenant service without losing the paper trail.'}
                    </h1>
                    <p>
                        {props.mode === 'tenant'
                            ? 'Submit issues for your rented asset, add access notes, and follow public updates from open to resolved.'
                            : 'Prioritize urgent work, assign owners or managers, separate public tenant updates from internal notes, and roll posted expenses into reports.'}
                    </p>
                    <div className="pmc-maintenance-command-meta">
                        <span>
                            <i className="bi bi-stopwatch" />
                            SLA due dates
                        </span>
                        <span>
                            <i className="bi bi-chat-square-text" />
                            Public/private timeline
                        </span>
                        <span>
                            <i className="bi bi-cash-coin" />
                            Expense rollups
                        </span>
                    </div>
                </div>
                <div className="pmc-maintenance-command-card">
                    <span>
                        {props.mode === 'tenant'
                            ? 'Active requests'
                            : 'Open backlog'}
                    </span>
                    <strong>{activeRequestCount.toLocaleString()}</strong>
                    <small>
                        {props.mode === 'tenant'
                            ? `${props.maintenanceInsights.resolved} resolved in your history`
                            : `${props.maintenanceInsights.unassigned} unassigned · ${props.maintenanceInsights.urgent} urgent`}
                    </small>
                </div>
            </section>

            <section className="pmc-maintenance-insight-grid mb-4">
                <MaintenanceInsight
                    icon="bi-wrench-adjustable"
                    label="Active work"
                    value={activeRequestCount.toLocaleString()}
                    detail={`${props.maintenanceInsights.open} open · ${props.maintenanceInsights.in_progress} in progress`}
                    tone="teal"
                />
                <MaintenanceInsight
                    icon="bi-lightning-charge"
                    label="Urgent"
                    value={props.maintenanceInsights.urgent.toLocaleString()}
                    detail="Needs same-day operational attention"
                    tone="orange"
                />
                <MaintenanceInsight
                    icon="bi-exclamation-triangle"
                    label="Overdue"
                    value={props.maintenanceInsights.overdue.toLocaleString()}
                    detail="Past due and not resolved or cancelled"
                    tone="red"
                />
                <MaintenanceInsight
                    icon="bi-receipt"
                    label="Posted costs"
                    value={currency(
                        props.maintenanceInsights.posted_expenses,
                        props.app.locale,
                    )}
                    detail={`${props.maintenanceInsights.total} total request${props.maintenanceInsights.total === 1 ? '' : 's'} tracked`}
                    tone="sand"
                />
            </section>

            <div className="row g-4 align-items-start">
                <div className="col-xl-4">
                    <div className="pmc-card p-4 pmc-maintenance-form-card">
                        <div className="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Maintenance workspace
                                </div>
                                <h2 className="h4 mb-0">
                                    {editing
                                        ? `Triage #${editing.id}`
                                        : 'Submit request'}
                                </h2>
                            </div>
                            {editing ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={clearTriage}
                                >
                                    Reset
                                </button>
                            ) : null}
                        </div>

                        {Object.keys(activeErrors).length > 0 ? (
                            <div className="alert alert-danger small">
                                {Object.values(activeErrors)[0]}
                            </div>
                        ) : null}

                        <div className="pmc-maintenance-form-guide mb-3">
                            <i
                                className={`bi ${props.mode === 'tenant' ? 'bi-house-heart' : 'bi-router'}`}
                            />
                            <div>
                                <strong>
                                    {props.mode === 'tenant'
                                        ? 'Start with the rented asset'
                                        : editing
                                          ? 'Triage changes are audited'
                                          : 'Create a clean service record'}
                                </strong>
                                <span>
                                    {props.mode === 'tenant'
                                        ? 'Only assets tied to your active lease are available here.'
                                        : editing
                                          ? 'Assignee, priority, status, notes, and public comments are stored in the request timeline.'
                                          : 'Pick the asset and tenant first. Assignment is optional until you know who owns the work.'}
                                </span>
                            </div>
                        </div>

                        <form className="d-grid gap-3" onSubmit={submit}>
                            {editing && props.mode === 'manager' ? (
                                <>
                                    <div>
                                        <label className="form-label pmc-form-label">
                                            Assigned to
                                        </label>
                                        <select
                                            className="form-select"
                                            value={
                                                managerForm.data
                                                    .assigned_to_user_id
                                            }
                                            onChange={(event) =>
                                                managerForm.setData(
                                                    'assigned_to_user_id',
                                                    event.currentTarget.value,
                                                )
                                            }
                                        >
                                            <option value="">Unassigned</option>
                                            {props.userOptions?.map((user) => (
                                                <option
                                                    key={user.id}
                                                    value={user.id}
                                                >
                                                    {user.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="row g-3">
                                        <div className="col-md-6">
                                            <label className="form-label pmc-form-label">
                                                Priority
                                            </label>
                                            <select
                                                className="form-select"
                                                value={
                                                    managerForm.data.priority
                                                }
                                                onChange={(event) =>
                                                    managerForm.setData(
                                                        'priority',
                                                        event.currentTarget
                                                            .value,
                                                    )
                                                }
                                            >
                                                {props.priorityOptions.map(
                                                    (priority) => (
                                                        <option
                                                            key={priority}
                                                            value={priority}
                                                        >
                                                            {humanLabel(
                                                                priority,
                                                            )}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label pmc-form-label">
                                                Status
                                            </label>
                                            <select
                                                className="form-select"
                                                value={managerForm.data.status}
                                                onChange={(event) =>
                                                    managerForm.setData(
                                                        'status',
                                                        event.currentTarget
                                                            .value,
                                                    )
                                                }
                                            >
                                                {props.statusOptions.map(
                                                    (status) => (
                                                        <option
                                                            key={status}
                                                            value={status}
                                                        >
                                                            {humanLabel(status)}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                        </div>
                                    </div>
                                    <textarea
                                        className="form-control"
                                        rows={3}
                                        placeholder="Internal notes"
                                        value={managerForm.data.internal_notes}
                                        onChange={(event) =>
                                            managerForm.setData(
                                                'internal_notes',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                    <textarea
                                        className="form-control"
                                        rows={3}
                                        placeholder="Update comment"
                                        value={managerForm.data.comment}
                                        onChange={(event) =>
                                            managerForm.setData(
                                                'comment',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                    <label className="form-check">
                                        <input
                                            type="checkbox"
                                            className="form-check-input"
                                            checked={
                                                managerForm.data
                                                    .is_public_comment
                                            }
                                            onChange={(event) =>
                                                managerForm.setData(
                                                    'is_public_comment',
                                                    event.currentTarget.checked,
                                                )
                                            }
                                        />
                                        <span className="form-check-label">
                                            Share update with tenant
                                        </span>
                                    </label>
                                </>
                            ) : (
                                <>
                                    <div>
                                        <label className="form-label pmc-form-label">
                                            Asset
                                        </label>
                                        <select
                                            className="form-select"
                                            value={
                                                props.mode === 'tenant'
                                                    ? tenantForm.data.asset_id
                                                    : managerForm.data.asset_id
                                            }
                                            onChange={(event) =>
                                                props.mode === 'tenant'
                                                    ? tenantForm.setData(
                                                          'asset_id',
                                                          event.currentTarget
                                                              .value,
                                                      )
                                                    : managerForm.setData(
                                                          'asset_id',
                                                          event.currentTarget
                                                              .value,
                                                      )
                                            }
                                            disabled={
                                                props.assetOptions.length === 0
                                            }
                                        >
                                            {props.assetOptions.length === 0 ? (
                                                <option value="">
                                                    No active rentable asset
                                                    available
                                                </option>
                                            ) : null}
                                            {props.assetOptions.map((asset) => (
                                                <option
                                                    key={asset.id}
                                                    value={asset.id}
                                                >
                                                    {asset.title_en}
                                                    {asset.code
                                                        ? ` · ${asset.code}`
                                                        : ''}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    {props.mode === 'manager' ? (
                                        <div>
                                            <label className="form-label pmc-form-label">
                                                Tenant
                                            </label>
                                            <select
                                                className="form-select"
                                                value={
                                                    managerForm.data
                                                        .tenant_profile_id
                                                }
                                                onChange={(event) =>
                                                    managerForm.setData(
                                                        'tenant_profile_id',
                                                        event.currentTarget
                                                            .value,
                                                    )
                                                }
                                                disabled={
                                                    (props.tenantOptions
                                                        ?.length ?? 0) === 0
                                                }
                                            >
                                                {(props.tenantOptions?.length ??
                                                    0) === 0 ? (
                                                    <option value="">
                                                        Add a tenant first
                                                    </option>
                                                ) : null}
                                                {props.tenantOptions?.map(
                                                    (tenant) => (
                                                        <option
                                                            key={tenant.id}
                                                            value={tenant.id}
                                                        >
                                                            {tenant.user
                                                                ?.name ??
                                                                `Tenant #${tenant.id}`}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                        </div>
                                    ) : null}

                                    <div className="row g-3">
                                        <div className="col-md-6">
                                            <label className="form-label pmc-form-label">
                                                Category
                                            </label>
                                            <select
                                                className="form-select"
                                                value={
                                                    props.mode === 'tenant'
                                                        ? tenantForm.data
                                                              .category
                                                        : managerForm.data
                                                              .category
                                                }
                                                onChange={(event) =>
                                                    props.mode === 'tenant'
                                                        ? tenantForm.setData(
                                                              'category',
                                                              event
                                                                  .currentTarget
                                                                  .value,
                                                          )
                                                        : managerForm.setData(
                                                              'category',
                                                              event
                                                                  .currentTarget
                                                                  .value,
                                                          )
                                                }
                                            >
                                                {props.categoryOptions.map(
                                                    (category) => (
                                                        <option
                                                            key={category}
                                                            value={category}
                                                        >
                                                            {humanLabel(
                                                                category,
                                                            )}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label pmc-form-label">
                                                Priority
                                            </label>
                                            <select
                                                className="form-select"
                                                value={
                                                    props.mode === 'tenant'
                                                        ? tenantForm.data
                                                              .priority
                                                        : managerForm.data
                                                              .priority
                                                }
                                                onChange={(event) =>
                                                    props.mode === 'tenant'
                                                        ? tenantForm.setData(
                                                              'priority',
                                                              event
                                                                  .currentTarget
                                                                  .value,
                                                          )
                                                        : managerForm.setData(
                                                              'priority',
                                                              event
                                                                  .currentTarget
                                                                  .value,
                                                          )
                                                }
                                            >
                                                {props.priorityOptions.map(
                                                    (priority) => (
                                                        <option
                                                            key={priority}
                                                            value={priority}
                                                        >
                                                            {humanLabel(
                                                                priority,
                                                            )}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                        </div>
                                    </div>

                                    {props.mode === 'manager' ? (
                                        <div>
                                            <label className="form-label pmc-form-label">
                                                Status
                                            </label>
                                            <select
                                                className="form-select"
                                                value={managerForm.data.status}
                                                onChange={(event) =>
                                                    managerForm.setData(
                                                        'status',
                                                        event.currentTarget
                                                            .value,
                                                    )
                                                }
                                            >
                                                {props.statusOptions.map(
                                                    (status) => (
                                                        <option
                                                            key={status}
                                                            value={status}
                                                        >
                                                            {humanLabel(status)}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                        </div>
                                    ) : null}

                                    <input
                                        className="form-control"
                                        placeholder="Title"
                                        value={
                                            props.mode === 'tenant'
                                                ? tenantForm.data.title
                                                : managerForm.data.title
                                        }
                                        onChange={(event) =>
                                            props.mode === 'tenant'
                                                ? tenantForm.setData(
                                                      'title',
                                                      event.currentTarget.value,
                                                  )
                                                : managerForm.setData(
                                                      'title',
                                                      event.currentTarget.value,
                                                  )
                                        }
                                    />

                                    <textarea
                                        className="form-control"
                                        rows={4}
                                        placeholder="Description"
                                        value={
                                            props.mode === 'tenant'
                                                ? tenantForm.data.description
                                                : managerForm.data.description
                                        }
                                        onChange={(event) =>
                                            props.mode === 'tenant'
                                                ? tenantForm.setData(
                                                      'description',
                                                      event.currentTarget.value,
                                                  )
                                                : managerForm.setData(
                                                      'description',
                                                      event.currentTarget.value,
                                                  )
                                        }
                                    />
                                </>
                            )}

                            <button
                                className="btn btn-primary"
                                disabled={
                                    (props.mode === 'tenant'
                                        ? tenantForm.processing
                                        : managerForm.processing) ||
                                    cannotSubmit
                                }
                            >
                                {editing ? 'Update request' : 'Submit request'}
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-xl-8">
                    {selectedRequest ? (
                        <MaintenanceDetailPanel
                            mode={props.mode}
                            requestItem={selectedRequest}
                            locale={props.app.locale}
                            comment={commentForm.data.comment}
                            commentProcessing={commentForm.processing}
                            onCommentChange={(value) =>
                                commentForm.setData('comment', value)
                            }
                            onCommentSubmit={submitTenantComment}
                            onTriage={() => startTriage(selectedRequest)}
                        />
                    ) : null}

                    <div className="pmc-card p-4">
                        <DataTable
                            title="Maintenance queue"
                            description="Search request titles, descriptions, categories, assets, or tenants."
                            data={props.requests}
                            filters={props.filters}
                            counts={props.counts}
                            basePath="/maintenance-requests"
                            createHref="/maintenance-requests/create"
                            createLabel="New request"
                            rowHref={(requestItem) =>
                                `/maintenance-requests/${requestItem.id}`
                            }
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
                                    render: (requestItem) => (
                                        <>
                                            <div className="fw-semibold">
                                                #{requestItem.id}{' '}
                                                {requestItem.title}
                                            </div>
                                            <div className="small text-secondary">
                                                {requestItem.category}
                                            </div>
                                            <div className="small text-secondary">
                                                Due{' '}
                                                {humanDate(
                                                    requestItem.due_at,
                                                    props.app.locale,
                                                )}
                                            </div>
                                            {requestItem.is_overdue ? (
                                                <span className="pmc-chip pmc-chip--danger mt-2">
                                                    Overdue
                                                </span>
                                            ) : null}
                                        </>
                                    ),
                                },
                                {
                                    key: 'asset',
                                    label: 'Asset',
                                    render: (requestItem) => (
                                        <>
                                            <div>
                                                {requestItem.asset?.title_en ??
                                                    '-'}
                                            </div>
                                            <div className="small text-secondary">
                                                {requestItem.tenant_profile
                                                    ?.user?.name ?? ''}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'assignment',
                                    label: 'Assignment',
                                    render: (requestItem) => (
                                        <>
                                            <div>
                                                {requestItem.assigned_to
                                                    ?.name ?? 'Unassigned'}
                                            </div>
                                            <div className="small text-secondary">
                                                {currency(
                                                    requestItem.expense_total,
                                                    props.app.locale,
                                                )}{' '}
                                                cost
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'priority',
                                    label: 'Priority',
                                    render: (requestItem) => (
                                        <StatusChip
                                            label={humanLabel(
                                                requestItem.priority,
                                            )}
                                            tone={
                                                requestItem.priority ===
                                                'urgent'
                                                    ? 'danger'
                                                    : requestItem.priority ===
                                                        'high'
                                                      ? 'warning'
                                                      : 'neutral'
                                            }
                                        />
                                    ),
                                },
                                {
                                    key: 'status',
                                    label: 'Status',
                                    render: (requestItem) => (
                                        <StatusChip
                                            label={humanLabel(
                                                requestItem.status,
                                            )}
                                            tone={
                                                requestItem.status ===
                                                'resolved'
                                                    ? 'success'
                                                    : requestItem.status ===
                                                        'cancelled'
                                                      ? 'neutral'
                                                      : 'primary'
                                            }
                                        />
                                    ),
                                },
                                {
                                    key: 'date',
                                    label: 'Date',
                                    render: (requestItem) =>
                                        humanDate(
                                            requestItem.created_at,
                                            props.app.locale,
                                        ),
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    className: 'text-end',
                                    render: (requestItem) => (
                                        <div className="d-flex justify-content-end gap-2 flex-wrap">
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary btn-sm"
                                                onClick={() =>
                                                    setSelectedRequestId(
                                                        requestItem.id,
                                                    )
                                                }
                                            >
                                                Details
                                            </button>
                                            {props.mode === 'manager' ? (
                                                <button
                                                    type="button"
                                                    className="btn btn-outline-secondary btn-sm"
                                                    onClick={() =>
                                                        startTriage(requestItem)
                                                    }
                                                >
                                                    Triage
                                                </button>
                                            ) : null}
                                            {requestItem.status !==
                                                'cancelled' &&
                                            requestItem.status !==
                                                'resolved' ? (
                                                <ArchiveAction
                                                    href={`/maintenance-requests/${requestItem.id}`}
                                                    label="Cancel"
                                                    confirmMessage={`Cancel maintenance request #${requestItem.id}?`}
                                                />
                                            ) : (
                                                <span className="text-secondary small">
                                                    Closed
                                                </span>
                                            )}
                                        </div>
                                    ),
                                },
                            ]}
                        />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

function MaintenanceDetailPanel({
    mode,
    requestItem,
    locale,
    comment,
    commentProcessing,
    onCommentChange,
    onCommentSubmit,
    onTriage,
}: {
    mode: 'tenant' | 'manager';
    requestItem: RequestRecord;
    locale: 'en' | 'ar';
    comment: string;
    commentProcessing: boolean;
    onCommentChange: (value: string) => void;
    onCommentSubmit: (event: FormEvent<HTMLFormElement>) => void;
    onTriage: () => void;
}) {
    return (
        <section className="pmc-card p-4 mb-4 pmc-maintenance-control-panel">
            <div className="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                <div>
                    <div className="pmc-kicker mb-2">Selected request</div>
                    <h2 className="h4 mb-1">
                        #{requestItem.id} {requestItem.title}
                    </h2>
                    <p className="text-secondary mb-0">
                        {requestItem.asset?.title_en ?? 'No asset'} ·{' '}
                        {requestItem.tenant_profile?.user?.name ?? 'No tenant'}
                    </p>
                </div>
                {mode === 'manager' ? (
                    <button
                        type="button"
                        className="btn btn-primary btn-sm"
                        onClick={onTriage}
                    >
                        Triage request
                    </button>
                ) : null}
            </div>

            <div className="pmc-maintenance-kpi-grid">
                <MaintenanceKpi
                    label="Status"
                    value={requestItem.status.replaceAll('_', ' ')}
                    detail={requestItem.priority}
                />
                <MaintenanceKpi
                    label="Due"
                    value={humanDate(requestItem.due_at, locale)}
                    detail={`Requested ${humanDate(requestItem.requested_at, locale)}`}
                />
                <MaintenanceKpi
                    label="Assigned"
                    value={requestItem.assigned_to?.name ?? 'Unassigned'}
                    detail={requestItem.category}
                />
                <MaintenanceKpi
                    label="Cost"
                    value={currency(requestItem.expense_total, locale)}
                    detail={`${requestItem.expense_count} expense entries`}
                />
            </div>

            <div className="row g-4 mt-1">
                <div className="col-lg-6">
                    <div className="pmc-kicker mb-2">Description</div>
                    <p className="text-secondary mb-3">
                        {requestItem.description}
                    </p>
                    {mode === 'manager' && requestItem.internal_notes ? (
                        <div className="pmc-maintenance-note">
                            <strong>Internal notes</strong>
                            <span>{requestItem.internal_notes}</span>
                        </div>
                    ) : null}
                    {mode === 'tenant' &&
                    !['resolved', 'cancelled'].includes(requestItem.status) ? (
                        <form
                            className="d-grid gap-2 mt-3"
                            onSubmit={onCommentSubmit}
                        >
                            <label className="form-label pmc-form-label">
                                Add update for owner
                            </label>
                            <textarea
                                className="form-control"
                                rows={3}
                                value={comment}
                                onChange={(event) =>
                                    onCommentChange(event.currentTarget.value)
                                }
                                placeholder="Add a photo note, access instruction, or extra detail..."
                            />
                            <button
                                className="btn btn-primary btn-sm justify-self-start"
                                disabled={commentProcessing}
                            >
                                Add comment
                            </button>
                        </form>
                    ) : null}
                </div>
                <div className="col-lg-6">
                    <div className="pmc-kicker mb-2">Timeline</div>
                    <div className="pmc-maintenance-timeline">
                        {requestItem.updates.length > 0 ? (
                            requestItem.updates.map((update) => (
                                <div key={update.id}>
                                    <span />
                                    <div>
                                        <strong>
                                            {update.status_to ??
                                                requestItem.status}
                                            {update.is_public_comment
                                                ? ' · public'
                                                : mode === 'manager'
                                                  ? ' · internal'
                                                  : ''}
                                        </strong>
                                        <p>{update.comment ?? 'Updated.'}</p>
                                        <small>
                                            {update.user ?? 'System'} ·{' '}
                                            {humanDate(
                                                update.created_at,
                                                locale,
                                            )}
                                        </small>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="pmc-inline-empty">
                                No updates recorded yet.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}

function MaintenanceKpi({
    label,
    value,
    detail,
}: {
    label: string;
    value: string;
    detail: string;
}) {
    return (
        <div>
            <span>{label}</span>
            <strong>{value}</strong>
            <small>{detail}</small>
        </div>
    );
}

function MaintenanceInsight({
    icon,
    label,
    value,
    detail,
    tone,
}: {
    icon: string;
    label: string;
    value: ReactNode;
    detail: string;
    tone: 'teal' | 'orange' | 'sand' | 'red';
}) {
    return (
        <div
            className={`pmc-maintenance-insight-card pmc-maintenance-insight-${tone}`}
        >
            <div>
                <i className={`bi ${icon}`} />
            </div>
            <span>{label}</span>
            <strong>{value}</strong>
            <small>{detail}</small>
        </div>
    );
}

function StatusChip({
    label,
    tone,
}: {
    label: string;
    tone: 'primary' | 'success' | 'warning' | 'danger' | 'neutral';
}) {
    return <span className={`pmc-chip pmc-chip--${tone}`}>{label}</span>;
}

function humanLabel(value: string) {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}
