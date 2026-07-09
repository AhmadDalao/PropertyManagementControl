import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import { humanDate } from '@/lib/utils';
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
    assigned_to_user_id?: number | null;
    internal_notes?: string | null;
    asset?: { title_en: string };
    tenant_profile?: { user?: { name: string } };
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
        });
        setEditing(requestItem);
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
