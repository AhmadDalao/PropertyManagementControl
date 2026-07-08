import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import { humanDate } from '@/lib/utils';
import type { SharedProps } from '@/types';

type RequestRecord = {
    id: number;
    title: string;
    status: string;
    category: string;
    priority: string;
    created_at: string;
    asset?: { title_en: string };
    tenant_profile?: { user?: { name: string } };
};

type PageProps = SharedProps & {
    mode: 'tenant' | 'manager';
    requests: RequestRecord[];
    assetOptions: Array<{ id: number; title_en: string }>;
    tenantProfile?: { id: number };
    tenantOptions?: Array<{ id: number; user?: { name: string } }>;
    userOptions?: Array<{ id: number; name: string }>;
};

export default function MaintenancePage() {
    const { props } = usePage<PageProps>();

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
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (props.mode === 'tenant') {
            tenantForm.post('/maintenance-requests', { preserveScroll: true });
            return;
        }

        managerForm.post('/maintenance-requests', { preserveScroll: true });
    };

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
                        <div className="pmc-kicker mb-2">Request form</div>
                        <form className="d-grid gap-3" onSubmit={submit}>
                            <div>
                                <label className="form-label pmc-form-label">Asset</label>
                                <select
                                    className="form-select"
                                    value={props.mode === 'tenant' ? tenantForm.data.asset_id : managerForm.data.asset_id}
                                    onChange={(event) =>
                                        props.mode === 'tenant'
                                            ? tenantForm.setData('asset_id', event.currentTarget.value)
                                            : managerForm.setData('asset_id', event.currentTarget.value)
                                    }
                                >
                                    {props.assetOptions.map((asset) => (
                                        <option key={asset.id} value={asset.id}>
                                            {asset.title_en}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {props.mode === 'manager' ? (
                                <div>
                                    <label className="form-label pmc-form-label">Tenant</label>
                                    <select
                                        className="form-select"
                                        value={managerForm.data.tenant_profile_id}
                                        onChange={(event) =>
                                            managerForm.setData('tenant_profile_id', event.currentTarget.value)
                                        }
                                    >
                                        {props.tenantOptions?.map((tenant) => (
                                            <option key={tenant.id} value={tenant.id}>
                                                {tenant.user?.name ?? `Tenant #${tenant.id}`}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            ) : null}

                            <div>
                                <label className="form-label pmc-form-label">Title</label>
                                <input
                                    className="form-control"
                                    value={props.mode === 'tenant' ? tenantForm.data.title : managerForm.data.title}
                                    onChange={(event) =>
                                        props.mode === 'tenant'
                                            ? tenantForm.setData('title', event.currentTarget.value)
                                            : managerForm.setData('title', event.currentTarget.value)
                                    }
                                />
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">Description</label>
                                <textarea
                                    className="form-control"
                                    rows={4}
                                    value={
                                        props.mode === 'tenant'
                                            ? tenantForm.data.description
                                            : managerForm.data.description
                                    }
                                    onChange={(event) =>
                                        props.mode === 'tenant'
                                            ? tenantForm.setData('description', event.currentTarget.value)
                                            : managerForm.setData('description', event.currentTarget.value)
                                    }
                                />
                            </div>

                            <button
                                className="btn btn-primary"
                                disabled={props.mode === 'tenant' ? tenantForm.processing : managerForm.processing}
                            >
                                Submit request
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-xl-8">
                    <div className="pmc-card p-4">
                        <div className="table-responsive">
                            <table className="table pmc-table">
                                <thead>
                                    <tr>
                                        <th>Request</th>
                                        <th>Asset</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.requests.map((requestItem) => (
                                        <tr key={requestItem.id}>
                                            <td>
                                                <div className="fw-semibold">{requestItem.title}</div>
                                                <div className="small text-secondary">{requestItem.category}</div>
                                            </td>
                                            <td>{requestItem.asset?.title_en ?? '-'}</td>
                                            <td>
                                                <span className="pmc-chip pmc-chip--primary">
                                                    {requestItem.status}
                                                </span>
                                            </td>
                                            <td>{humanDate(requestItem.created_at, props.app.locale)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
