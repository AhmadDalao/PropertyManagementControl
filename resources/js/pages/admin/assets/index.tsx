import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency } from '@/lib/utils';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

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
    code: string;
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
    parent?: { title_en: string } | null;
};

type PageProps = SharedProps & {
    assets: PaginatedData<AssetRecord>;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    parentOptions: Array<{ id: number; name: string }>;
    userOptions: Array<{ id: number; name: string; portfolio_id: number }>;
};

export default function AssetsPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<AssetRecord | null>(null);
    const form = useForm({
        portfolio_id: String(
            props.auth.user?.portfolio_id ??
                props.portfolioOptions[0]?.id ??
                '',
        ),
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

    const startEditing = (asset: AssetRecord) => {
        const owner = asset.stakeholders?.find(
            (item) => item.relationship_type === 'owner',
        );
        const manager = asset.stakeholders?.find(
            (item) => item.relationship_type === 'manager',
        );
        form.setData({
            portfolio_id: String(asset.portfolio_id),
            parent_id: asset.parent_id ? String(asset.parent_id) : '',
            asset_type: asset.asset_type,
            usage_type: asset.usage_type,
            title_en: asset.title_en,
            title_ar: asset.title_ar,
            code: asset.code,
            status: asset.status,
            occupancy_status: asset.occupancy_status,
            rentable: asset.rentable,
            valuation_amount: asset.valuation_amount,
            currency: asset.currency,
            area: Number(asset.area ?? 0),
            level_label: asset.level_label ?? '',
            unit_label: asset.unit_label ?? '',
            address: asset.address ?? '',
            description_en: asset.description_en ?? '',
            description_ar: asset.description_ar ?? '',
            primary_owner_user_id: owner?.user?.id ? String(owner.user.id) : '',
            primary_manager_user_id: manager?.user?.id
                ? String(manager.user.id)
                : '',
        });
        setEditing(asset);
    };

    const clearEditing = () => {
        setEditing(null);
        form.reset();
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/assets/${editing.id}`, {
                preserveScroll: true,
                onSuccess: clearEditing,
            });

            return;
        }

        form.post('/assets', { preserveScroll: true });
    };

    const filterFields: TableFilterField[] = [
        {
            name: 'status',
            label: 'Status',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Active', value: 'active' },
                { label: 'Inactive', value: 'inactive' },
                { label: 'Archived', value: 'archived' },
            ],
        },
        {
            name: 'asset_type',
            label: 'Type',
            options: assetTypeOptions('All'),
        },
        {
            name: 'usage_type',
            label: 'Usage',
            options: usageTypeOptions('All'),
        },
        {
            name: 'occupancy_status',
            label: 'Occupancy',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Vacant', value: 'vacant' },
                { label: 'Occupied', value: 'occupied' },
                { label: 'Reserved', value: 'reserved' },
                { label: 'Maintenance', value: 'maintenance' },
            ],
        },
        {
            name: 'rentable',
            label: 'Rentable',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Yes', value: 'yes' },
                { label: 'No', value: 'no' },
            ],
        },
    ];

    if (props.auth.user?.roles.includes('superadmin')) {
        filterFields.push({
            name: 'portfolio_id',
            label: 'Portfolio',
            options: [
                { label: 'All', value: 'all' },
                ...props.portfolioOptions.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

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
                                <div className="pmc-kicker mb-2">
                                    Asset form
                                </div>
                                <h2 className="h4 mb-0">
                                    {editing
                                        ? `Edit ${editing.title_en}`
                                        : 'Create asset'}
                                </h2>
                            </div>
                            {editing ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={clearEditing}
                                >
                                    Reset
                                </button>
                            ) : null}
                        </div>

                        <form className="d-grid gap-3" onSubmit={submit}>
                            {props.auth.user?.roles.includes('superadmin') ? (
                                <div>
                                    <label className="form-label pmc-form-label">
                                        Portfolio
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.portfolio_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'portfolio_id',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        {props.portfolioOptions.map(
                                            (portfolio) => (
                                                <option
                                                    key={portfolio.id}
                                                    value={portfolio.id}
                                                >
                                                    {portfolio.name}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </div>
                            ) : null}

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Type
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.asset_type}
                                        onChange={(event) =>
                                            form.setData(
                                                'asset_type',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        {assetTypeOptions().map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Usage
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.usage_type}
                                        onChange={(event) =>
                                            form.setData(
                                                'usage_type',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        {usageTypeOptions().map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">
                                    Parent asset
                                </label>
                                <select
                                    className="form-select"
                                    value={form.data.parent_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'parent_id',
                                            event.currentTarget.value,
                                        )
                                    }
                                >
                                    <option value="">No parent</option>
                                    {props.parentOptions.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <input
                                className="form-control"
                                placeholder="English title"
                                value={form.data.title_en}
                                onChange={(event) =>
                                    form.setData(
                                        'title_en',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <input
                                className="form-control"
                                placeholder="Arabic title"
                                value={form.data.title_ar}
                                onChange={(event) =>
                                    form.setData(
                                        'title_ar',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            {!editing ? (
                                <input
                                    className="form-control"
                                    placeholder="Code"
                                    value={form.data.code}
                                    onChange={(event) =>
                                        form.setData(
                                            'code',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            ) : null}

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Status
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.status}
                                        onChange={(event) =>
                                            form.setData(
                                                'status',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">
                                            Inactive
                                        </option>
                                        <option value="archived">
                                            Archived
                                        </option>
                                    </select>
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Occupancy
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.occupancy_status}
                                        onChange={(event) =>
                                            form.setData(
                                                'occupancy_status',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="vacant">Vacant</option>
                                        <option value="occupied">
                                            Occupied
                                        </option>
                                        <option value="reserved">
                                            Reserved
                                        </option>
                                        <option value="maintenance">
                                            Maintenance
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Value
                                    </label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={form.data.valuation_amount}
                                        onChange={(event) =>
                                            form.setData(
                                                'valuation_amount',
                                                Number(
                                                    event.currentTarget.value,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Area
                                    </label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={form.data.area}
                                        onChange={(event) =>
                                            form.setData(
                                                'area',
                                                Number(
                                                    event.currentTarget.value,
                                                ),
                                            )
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
                                    onChange={(event) =>
                                        form.setData(
                                            'rentable',
                                            event.currentTarget.checked,
                                        )
                                    }
                                />
                                <label
                                    htmlFor="rentable"
                                    className="form-check-label"
                                >
                                    Rentable
                                </label>
                            </div>

                            <button
                                className="btn btn-primary"
                                disabled={form.processing}
                            >
                                {editing ? 'Update asset' : 'Create asset'}
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-xl-8">
                    <div className="pmc-card p-4">
                        <DataTable
                            title="Asset register"
                            description="Search by title, Arabic title, code, parent, address, owner, or manager."
                            data={props.assets}
                            filters={props.filters}
                            counts={props.counts}
                            basePath="/assets"
                            exportHref={exportUrl(
                                '/exports/assets',
                                props.filters,
                            )}
                            filterFields={filterFields}
                            columns={[
                                {
                                    key: 'asset',
                                    label: 'Asset',
                                    render: (asset) => (
                                        <>
                                            <div className="fw-semibold">
                                                {asset.title_en}
                                            </div>
                                            <div className="small text-secondary">
                                                {asset.title_ar}
                                            </div>
                                            <span className="pmc-chip mt-2">
                                                {asset.code}
                                            </span>
                                        </>
                                    ),
                                },
                                {
                                    key: 'type',
                                    label: 'Type',
                                    render: (asset) => (
                                        <>
                                            <div>{asset.asset_type}</div>
                                            <div className="small text-secondary">
                                                {asset.usage_type}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'occupancy',
                                    label: 'Occupancy',
                                    render: (asset) => (
                                        <span className="pmc-chip pmc-chip--primary">
                                            {asset.occupancy_status}
                                        </span>
                                    ),
                                },
                                {
                                    key: 'value',
                                    label: 'Value',
                                    render: (asset) =>
                                        currency(
                                            asset.valuation_amount,
                                            props.app.locale,
                                            asset.currency,
                                        ),
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    className: 'text-end',
                                    render: (asset) => (
                                        <div className="d-flex justify-content-end gap-2 flex-wrap">
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary btn-sm"
                                                onClick={() =>
                                                    startEditing(asset)
                                                }
                                            >
                                                Edit
                                            </button>
                                            {asset.status !== 'archived' ? (
                                                <ArchiveAction
                                                    href={`/assets/${asset.id}`}
                                                    confirmMessage={`Archive ${asset.title_en}? Active leases must be terminated first.`}
                                                />
                                            ) : null}
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

function assetTypeOptions(allLabel?: string) {
    return [
        ...(allLabel ? [{ label: allLabel, value: 'all' }] : []),
        { label: 'Property', value: 'property' },
        { label: 'Building', value: 'building' },
        { label: 'Floor', value: 'floor' },
        { label: 'Unit', value: 'unit' },
        { label: 'Space', value: 'space' },
    ];
}

function usageTypeOptions(allLabel?: string) {
    return [
        ...(allLabel ? [{ label: allLabel, value: 'all' }] : []),
        { label: 'Residential', value: 'residential' },
        { label: 'Commercial', value: 'commercial' },
        { label: 'Mixed', value: 'mixed' },
        { label: 'Personal', value: 'personal' },
    ];
}
