import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency, humanDate } from '@/lib/utils';
import type { SharedProps } from '@/types';

type LeaseRecord = {
    id: number;
    portfolio_id: number;
    tenant_profile_id: number;
    leaseable_id: number;
    code: string;
    status: string;
    payment_frequency: string;
    started_at: string;
    ends_at: string;
    signed_at?: string | null;
    rent_amount: number;
    deposit_amount: number;
    currency: string;
    notes?: string | null;
    tenant_profile?: { user?: { name: string } };
    leaseable?: { title_en: string };
};

type PageProps = SharedProps & {
    leases: LeaseRecord[];
    portfolioOptions: Array<{ id: number; name: string }>;
    tenantOptions: Array<{ id: number; user?: { name: string } }>;
    assetOptions: Array<{ id: number; title_en: string }>;
};

export default function LeasesPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<LeaseRecord | null>(null);
    const [signedContractLease, setSignedContractLease] = useState<string>('');
    const signedContractForm = useForm<{ signed_contract: File | null }>({
        signed_contract: null,
    });

    const form = useForm({
        portfolio_id: String(props.auth.user?.portfolio_id ?? props.portfolioOptions[0]?.id ?? ''),
        tenant_profile_id: String(props.tenantOptions[0]?.id ?? ''),
        asset_id: String(props.assetOptions[0]?.id ?? ''),
        status: 'active',
        payment_frequency: 'monthly',
        started_at: '',
        ends_at: '',
        signed_at: '',
        rent_amount: 0,
        deposit_amount: 0,
        tax_amount: 0,
        discount_amount: 0,
        currency: 'SAR',
        billing_day: 1,
        notes: '',
        resync_installments: false,
    });

    useEffect(() => {
        if (!editing) {
            form.reset();
            return;
        }

        form.setData({
            portfolio_id: String(editing.portfolio_id),
            tenant_profile_id: String(editing.tenant_profile_id),
            asset_id: String(editing.leaseable_id),
            status: editing.status,
            payment_frequency: editing.payment_frequency,
            started_at: editing.started_at,
            ends_at: editing.ends_at,
            signed_at: editing.signed_at ?? '',
            rent_amount: editing.rent_amount,
            deposit_amount: editing.deposit_amount,
            tax_amount: 0,
            discount_amount: 0,
            currency: editing.currency,
            billing_day: 1,
            notes: editing.notes ?? '',
            resync_installments: false,
        });
    }, [editing]);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/leases/${editing.id}`, {
                preserveScroll: true,
                onSuccess: () => setEditing(null),
            });
            return;
        }

        form.post('/leases', { preserveScroll: true });
    };

    const uploadSignedContract = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!signedContractLease) {
            return;
        }

        signedContractForm.post(`/leases/${signedContractLease}/signed-contract`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                signedContractForm.reset();
                setSignedContractLease('');
            },
        });
    };

    return (
        <AdminLayout>
            <Head title="Leases" />
            <PageHeader
                title="Leases"
                description="Create rent contracts, generate PDFs, upload signed files, and control installment schedules."
            />

            <div className="row g-4">
                <div className="col-xl-4">
                    <div className="pmc-card p-4 mb-4">
                        <div className="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">Lease form</div>
                                <h2 className="h4 mb-0">
                                    {editing ? `Edit ${editing.code}` : 'Create lease'}
                                </h2>
                            </div>
                            {editing ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={() => setEditing(null)}
                                >
                                    Reset
                                </button>
                            ) : null}
                        </div>

                        <form className="d-grid gap-3" onSubmit={submit}>
                            {props.auth.user?.roles.includes('superadmin') ? (
                                <div>
                                    <label className="form-label pmc-form-label">Portfolio</label>
                                    <select
                                        className="form-select"
                                        value={form.data.portfolio_id}
                                        onChange={(event) =>
                                            form.setData('portfolio_id', event.currentTarget.value)
                                        }
                                    >
                                        {props.portfolioOptions.map((portfolio) => (
                                            <option key={portfolio.id} value={portfolio.id}>
                                                {portfolio.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            ) : null}

                            <div>
                                <label className="form-label pmc-form-label">Tenant</label>
                                <select
                                    className="form-select"
                                    value={form.data.tenant_profile_id}
                                    onChange={(event) =>
                                        form.setData('tenant_profile_id', event.currentTarget.value)
                                    }
                                >
                                    {props.tenantOptions.map((tenant) => (
                                        <option key={tenant.id} value={tenant.id}>
                                            {tenant.user?.name ?? `Tenant #${tenant.id}`}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">Asset</label>
                                <select
                                    className="form-select"
                                    value={form.data.asset_id}
                                    onChange={(event) => form.setData('asset_id', event.currentTarget.value)}
                                >
                                    {props.assetOptions.map((asset) => (
                                        <option key={asset.id} value={asset.id}>
                                            {asset.title_en}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Start</label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={form.data.started_at}
                                        onChange={(event) => form.setData('started_at', event.currentTarget.value)}
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">End</label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={form.data.ends_at}
                                        onChange={(event) => form.setData('ends_at', event.currentTarget.value)}
                                    />
                                </div>
                            </div>

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Rent</label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={form.data.rent_amount}
                                        onChange={(event) =>
                                            form.setData('rent_amount', Number(event.currentTarget.value))
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Deposit</label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={form.data.deposit_amount}
                                        onChange={(event) =>
                                            form.setData('deposit_amount', Number(event.currentTarget.value))
                                        }
                                    />
                                </div>
                            </div>

                            <button className="btn btn-primary" disabled={form.processing}>
                                {editing ? 'Update lease' : 'Create lease'}
                            </button>
                        </form>
                    </div>

                    <div className="pmc-card p-4">
                        <div className="pmc-kicker mb-2">Signed contract upload</div>
                        <form className="d-grid gap-3" onSubmit={uploadSignedContract}>
                            <select
                                className="form-select"
                                value={signedContractLease}
                                onChange={(event) => setSignedContractLease(event.currentTarget.value)}
                            >
                                <option value="">Choose lease</option>
                                {props.leases.map((lease) => (
                                    <option key={lease.id} value={lease.id}>
                                        {lease.code}
                                    </option>
                                ))}
                            </select>
                            <input
                                type="file"
                                className="form-control"
                                onChange={(event) =>
                                    signedContractForm.setData(
                                        'signed_contract',
                                        event.currentTarget.files?.[0] ?? null,
                                    )
                                }
                            />
                            <button className="btn btn-outline-secondary" disabled={signedContractForm.processing}>
                                Upload signed contract
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
                                        <th>Lease</th>
                                        <th>Tenant</th>
                                        <th>Period</th>
                                        <th>Rent</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.leases.map((lease) => (
                                        <tr key={lease.id}>
                                            <td>
                                                <div className="fw-semibold">{lease.code}</div>
                                                <span className="pmc-chip pmc-chip--primary">{lease.status}</span>
                                            </td>
                                            <td>{lease.tenant_profile?.user?.name ?? '-'}</td>
                                            <td>
                                                {humanDate(lease.started_at, props.app.locale)} to{' '}
                                                {humanDate(lease.ends_at, props.app.locale)}
                                            </td>
                                            <td>{currency(lease.rent_amount, props.app.locale, lease.currency)}</td>
                                            <td className="text-end">
                                                <div className="d-flex justify-content-end gap-2">
                                                    <a href={`/leases/${lease.id}/contract`} className="btn btn-outline-secondary btn-sm">
                                                        Contract
                                                    </a>
                                                    <a href={`/leases/${lease.id}/statement`} className="btn btn-outline-secondary btn-sm">
                                                        Statement
                                                    </a>
                                                    <button
                                                        type="button"
                                                        className="btn btn-primary btn-sm"
                                                        onClick={() => setEditing(lease)}
                                                    >
                                                        Edit
                                                    </button>
                                                </div>
                                            </td>
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
