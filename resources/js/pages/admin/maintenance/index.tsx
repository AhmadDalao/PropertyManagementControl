import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
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

type PageProps = SharedProps & {
    mode: 'tenant' | 'manager';
    requests: PaginatedData<RequestRecord>;
    filters: TableFilters;
    counts: TableCount[];
    assetOptions: Array<{ id: number; title_en: string }>;
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
        assigned_to_user_id: String(props.userOptions?.[0]?.id ?? ''),
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
                { label: 'Open', value: 'open' },
                { label: 'In progress', value: 'in_progress' },
                { label: 'Resolved', value: 'resolved' },
                { label: 'Cancelled', value: 'cancelled' },
            ],
        },
        {
            name: 'category',
            label: 'Category',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Electricity', value: 'electricity' },
                { label: 'Plumbing', value: 'plumbing' },
                { label: 'AC', value: 'ac' },
                { label: 'General', value: 'general' },
            ],
        },
        {
            name: 'priority',
            label: 'Priority',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Low', value: 'low' },
                { label: 'Medium', value: 'medium' },
                { label: 'High', value: 'high' },
                { label: 'Urgent', value: 'urgent' },
            ],
        },
        { name: 'date_from', label: 'From', type: 'date' },
        { name: 'date_to', label: 'To', type: 'date' },
    ];

    return (
        <AdminLayout>
            <Head title="Maintenance" />
            <PageHeader
                title="Maintenance"
                description="Submit requests, assign work, and track service history."
            />

            <div className="row g-4">
                <div className="col-xl-4">
                    <div className="pmc-card p-4">
                        <div className="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Request form
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
                                                <option value="low">Low</option>
                                                <option value="medium">
                                                    Medium
                                                </option>
                                                <option value="high">
                                                    High
                                                </option>
                                                <option value="urgent">
                                                    Urgent
                                                </option>
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
                                                <option value="open">
                                                    Open
                                                </option>
                                                <option value="in_progress">
                                                    In progress
                                                </option>
                                                <option value="resolved">
                                                    Resolved
                                                </option>
                                                <option value="cancelled">
                                                    Cancelled
                                                </option>
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
                                        >
                                            {props.assetOptions.map((asset) => (
                                                <option
                                                    key={asset.id}
                                                    value={asset.id}
                                                >
                                                    {asset.title_en}
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
                                            >
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
                                                <option value="electricity">
                                                    Electricity
                                                </option>
                                                <option value="plumbing">
                                                    Plumbing
                                                </option>
                                                <option value="ac">AC</option>
                                                <option value="general">
                                                    General
                                                </option>
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
                                                <option value="low">Low</option>
                                                <option value="medium">
                                                    Medium
                                                </option>
                                                <option value="high">
                                                    High
                                                </option>
                                                <option value="urgent">
                                                    Urgent
                                                </option>
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
                                                <option value="open">
                                                    Open
                                                </option>
                                                <option value="in_progress">
                                                    In progress
                                                </option>
                                                <option value="resolved">
                                                    Resolved
                                                </option>
                                                <option value="cancelled">
                                                    Cancelled
                                                </option>
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
                                    props.mode === 'tenant'
                                        ? tenantForm.processing
                                        : managerForm.processing
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
                                        <span className="pmc-chip">
                                            {requestItem.priority}
                                        </span>
                                    ),
                                },
                                {
                                    key: 'status',
                                    label: 'Status',
                                    render: (requestItem) => (
                                        <span className="pmc-chip pmc-chip--primary">
                                            {requestItem.status}
                                        </span>
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
