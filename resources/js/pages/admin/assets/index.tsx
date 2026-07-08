import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency } from '@/lib/utils';
import type { SharedProps } from '@/types';

type StakeholderRecord = {
    relationship_type: string;
    user?: { id: number; name: string } | null;
};

type AssetRecord = {
    id: number;
    portfolio_id: number;
    parent_id?: number | null;
    asset_type: string;
    usage_type: string;
    title_en: string;
    title_ar: string;
    status: string;
    occupancy_status: string;
    rentable: boolean;
    valuation_amount: number;
    currency: string;
    area?: number | null;
    level_label?: string | null;
    unit_label?: string | null;
    address?: string | null;
    description_en?: string | null;
    description_ar?: string | null;
    stakeholders?: StakeholderRecord[];
};

type PageProps = SharedProps & {
    assets: AssetRecord[];
    portfolioOptions: Array<{ id: number; name: string }>;
    parentOptions: Array<{ id: number; name: string }>;
    userOptions: Array<{ id: number; name: string; portfolio_id: number }>;
};

export default function AssetsPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<AssetRecord | null>(null);
    const form = useForm({
        portfolio_id: String(props.auth.user?.portfolio_id ?? props.portfolioOptions[0]?.id ?? ''),
        parent_id: '',
        asset_type: 'building',
        usage_type: 'residential',
        title_en: '',
        title_ar: '',
        code: '',
        status: 'active',
        occupancy_status: 'vacant',
        rentable: false,
        valuation_amount: 0,
        currency: 'SAR',
        area: 0,
        level_label: '',
        unit_label: '',
        address: '',
        description_en: '',
        description_ar: '',
        primary_owner_user_id: '',
        primary_manager_user_id: '',
    });

    useEffect(() => {
        if (!editing) {
            form.reset();
            return;
        }

        const owner = editing.stakeholders?.find((item) => item.relationship_type === 'owner');
        const manager = editing.stakeholders?.find((item) => item.relationship_type === 'manager');

        form.setData({
            portfolio_id: String(editing.portfolio_id),
            parent_id: editing.parent_id ? String(editing.parent_id) : '',
            asset_type: editing.asset_type,
            usage_type: editing.usage_type,
            title_en: editing.title_en,
            title_ar: editing.title_ar,
            code: '',
            status: editing.status,
            occupancy_status: editing.occupancy_status,
            rentable: editing.rentable,
            valuation_amount: editing.valuation_amount,
            currency: editing.currency,
            area: Number(editing.area ?? 0),
            level_label: editing.level_label ?? '',
            unit_label: editing.unit_label ?? '',
            address: editing.address ?? '',
            description_en: editing.description_en ?? '',
            description_ar: editing.description_ar ?? '',
            primary_owner_user_id: owner?.user?.id ? String(owner.user.id) : '',
            primary_manager_user_id: manager?.user?.id ? String(manager.user.id) : '',
        });
    }, [editing]);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/assets/${editing.id}`, {
                preserveScroll: true,
                onSuccess: () => setEditing(null),
            });
            return;
        }

        form.post('/assets', { preserveScroll: true });
    };

    return (
        <AdminLayout>
            <Head title="Assets" />
            <PageHeader
                title="Assets"
                description="Track buildings, floors, units, departments, valuation, and who owns or manages them."
            />

            <div className="row g-4">
                <div className="col-xl-4">
                    <div className="pmc-card p-4">
                        <div className="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">Asset form</div>
                                <h2 className="h4 mb-0">
                                    {editing ? `Edit ${editing.title_en}` : 'Create asset'}
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

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Type</label>
                                    <select
                                        className="form-select"
                                        value={form.data.asset_type}
                                        onChange={(event) =>
                                            form.setData('asset_type', event.currentTarget.value)
                                        }
                                    >
                                        <option value="property">Property</option>
                                        <option value="building">Building</option>
                                        <option value="floor">Floor</option>
                                        <option value="unit">Unit</option>
                                        <option value="space">Space</option>
                                    </select>
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Usage</label>
                                    <select
                                        className="form-select"
                                        value={form.data.usage_type}
                                        onChange={(event) =>
                                            form.setData('usage_type', event.currentTarget.value)
                                        }
                                    >
                                        <option value="residential">Residential</option>
                                        <option value="commercial">Commercial</option>
                                        <option value="mixed">Mixed</option>
                                        <option value="personal">Personal</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">Parent asset</label>
                                <select
                                    className="form-select"
                                    value={form.data.parent_id}
                                    onChange={(event) => form.setData('parent_id', event.currentTarget.value)}
                                >
                                    <option value="">No parent</option>
                                    {props.parentOptions.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">English title</label>
                                <input
                                    className="form-control"
                                    value={form.data.title_en}
                                    onChange={(event) => form.setData('title_en', event.currentTarget.value)}
                                />
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">Arabic title</label>
                                <input
                                    className="form-control"
                                    value={form.data.title_ar}
                                    onChange={(event) => form.setData('title_ar', event.currentTarget.value)}
                                />
                            </div>

                            {!editing ? (
                                <div>
                                    <label className="form-label pmc-form-label">Code</label>
                                    <input
                                        className="form-control"
                                        value={form.data.code}
                                        onChange={(event) => form.setData('code', event.currentTarget.value)}
                                    />
                                </div>
                            ) : null}

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Value</label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={form.data.valuation_amount}
                                        onChange={(event) =>
                                            form.setData('valuation_amount', Number(event.currentTarget.value))
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Area</label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={form.data.area}
                                        onChange={(event) =>
                                            form.setData('area', Number(event.currentTarget.value))
                                        }
                                    />
                                </div>
                            </div>

                            <div className="form-check">
                                <input
                                    id="rentable"
                                    type="checkbox"
                                    className="form-check-input"
                                    checked={form.data.rentable}
                                    onChange={(event) => form.setData('rentable', event.currentTarget.checked)}
                                />
                                <label htmlFor="rentable" className="form-check-label">
                                    Rentable
                                </label>
                            </div>

                            <button className="btn btn-primary" disabled={form.processing}>
                                {editing ? 'Update asset' : 'Create asset'}
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
                                        <th>Asset</th>
                                        <th>Type</th>
                                        <th>Occupancy</th>
                                        <th>Value</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.assets.map((asset) => (
                                        <tr key={asset.id}>
                                            <td>
                                                <div className="fw-semibold">{asset.title_en}</div>
                                                <div className="small text-secondary">{asset.title_ar}</div>
                                            </td>
                                            <td>{asset.asset_type}</td>
                                            <td>
                                                <span className="pmc-chip pmc-chip--primary">
                                                    {asset.occupancy_status}
                                                </span>
                                            </td>
                                            <td>{currency(asset.valuation_amount, props.app.locale, asset.currency)}</td>
                                            <td className="text-end">
                                                <button
                                                    type="button"
                                                    className="btn btn-outline-secondary btn-sm"
                                                    onClick={() => setEditing(asset)}
                                                >
                                                    Edit
                                                </button>
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
