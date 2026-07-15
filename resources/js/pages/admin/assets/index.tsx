import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { ArchiveAction } from '@/components/archive-action';
import { CreatePageShortcut } from '@/components/create-page-shortcut';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
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
    children_count?: number;
    active_leases_count?: number;
};

type AssetInsights = {
    total_assets: number;
    total_value: number;
    rentable_assets: number;
    vacant_rentable_assets: number;
    occupied_assets: number;
    maintenance_assets: number;
    buildings: number;
    floors: number;
    units: number;
    spaces: number;
    missing_owner: number;
    missing_manager: number;
    rentable_occupancy_rate: number;
};

type PageProps = SharedProps & {
    assets: PaginatedData<AssetRecord>;
    filters: TableFilters;
    counts: TableCount[];
    insights: AssetInsights;
    portfolioOptions: Array<{ id: number; name: string }>;
    parentOptions: Array<{
        id: number;
        name: string;
        code: string;
        asset_type: string;
        portfolio_id: number;
    }>;
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

    const selectedPortfolioId = Number(form.data.portfolio_id || 0);
    const scopedUsers = props.userOptions.filter(
        (user) =>
            selectedPortfolioId === 0 ||
            user.portfolio_id === selectedPortfolioId,
    );
    const scopedParents = props.parentOptions.filter(
        (asset) =>
            (selectedPortfolioId === 0 ||
                asset.portfolio_id === selectedPortfolioId) &&
            asset.id !== editing?.id,
    );
    const ownerCandidates = scopedUsers;
    const managerCandidates = scopedUsers;

    return (
        <AdminLayout>
            <Head title="Assets" />
            <PageHeader
                title="Assets"
                description="Track buildings, floors, units, departments, valuation, and who owns or manages them."
            />

            <section className="pmc-asset-control-hero">
                <div>
                    <div className="pmc-kicker mb-2">Asset control cycle</div>
                    <h2>Build the property tree before leases and reports.</h2>
                    <p>
                        Create the hierarchy, assign ownership and management,
                        mark only rentable nodes, then track occupancy from the
                        table.
                    </p>
                    <CreatePageShortcut
                        href="/assets/create"
                        label="Create asset"
                        icon="bi-building-add"
                        description="Open a dedicated asset form for property, building, floor, unit, or space details."
                    />
                </div>
                <div className="pmc-asset-flow">
                    <span>Property</span>
                    <i className="bi bi-arrow-right" />
                    <span>Building</span>
                    <i className="bi bi-arrow-right" />
                    <span>Floor</span>
                    <i className="bi bi-arrow-right" />
                    <span>Unit / Space</span>
                </div>
            </section>

            <div className="row g-3 mb-4">
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Assets"
                        value={props.insights.total_assets}
                        hint={`${props.insights.buildings} buildings · ${props.insights.units} units`}
                        tone="accent"
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Portfolio value"
                        value={currency(
                            props.insights.total_value,
                            props.app.locale,
                        )}
                        hint={`${props.insights.spaces} spaces · ${props.insights.floors} floors`}
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Rentable occupancy"
                        value={`${props.insights.rentable_occupancy_rate}%`}
                        hint={`${props.insights.vacant_rentable_assets} vacant of ${props.insights.rentable_assets} rentable`}
                        tone="teal"
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Assignment gaps"
                        value={
                            props.insights.missing_owner +
                            props.insights.missing_manager
                        }
                        hint={`${props.insights.missing_owner} missing owner · ${props.insights.missing_manager} missing manager`}
                    />
                </div>
            </div>

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
                                        onChange={(event) => {
                                            form.setData({
                                                ...form.data,
                                                portfolio_id:
                                                    event.currentTarget.value,
                                                parent_id: '',
                                                primary_owner_user_id: '',
                                                primary_manager_user_id: '',
                                            });
                                        }}
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
                                    {scopedParents.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.name} · {item.code} ·{' '}
                                            {item.asset_type}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">
                                    English title
                                </label>
                                <input
                                    className="form-control"
                                    value={form.data.title_en}
                                    onChange={(event) =>
                                        form.setData(
                                            'title_en',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <label className="form-label pmc-form-label">
                                    Arabic title
                                </label>
                                <input
                                    className="form-control"
                                    value={form.data.title_ar}
                                    onChange={(event) =>
                                        form.setData(
                                            'title_ar',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>
                            {!editing ? (
                                <div>
                                    <label className="form-label pmc-form-label">
                                        Code
                                    </label>
                                    <input
                                        className="form-control"
                                        value={form.data.code}
                                        onChange={(event) =>
                                            form.setData(
                                                'code',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
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

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Currency
                                    </label>
                                    <input
                                        className="form-control text-uppercase"
                                        maxLength={3}
                                        value={form.data.currency}
                                        onChange={(event) =>
                                            form.setData(
                                                'currency',
                                                event.currentTarget.value.toUpperCase(),
                                            )
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Level / floor label
                                    </label>
                                    <input
                                        className="form-control"
                                        value={form.data.level_label}
                                        onChange={(event) =>
                                            form.setData(
                                                'level_label',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">
                                    Unit / space label
                                </label>
                                <input
                                    className="form-control"
                                    value={form.data.unit_label}
                                    onChange={(event) =>
                                        form.setData(
                                            'unit_label',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="pmc-asset-assignment-box">
                                <div>
                                    <div className="pmc-kicker mb-1">
                                        Ownership and management
                                    </div>
                                    <strong>
                                        Assign the people responsible for this
                                        asset.
                                    </strong>
                                </div>
                                <label>
                                    <span>Primary owner</span>
                                    <select
                                        className="form-select"
                                        value={form.data.primary_owner_user_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'primary_owner_user_id',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="">No owner</option>
                                        {ownerCandidates.map((user) => (
                                            <option
                                                key={user.id}
                                                value={user.id}
                                            >
                                                {user.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <label>
                                    <span>Primary manager</span>
                                    <select
                                        className="form-select"
                                        value={
                                            form.data.primary_manager_user_id
                                        }
                                        onChange={(event) =>
                                            form.setData(
                                                'primary_manager_user_id',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="">No manager</option>
                                        {managerCandidates.map((user) => (
                                            <option
                                                key={user.id}
                                                value={user.id}
                                            >
                                                {user.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                            </div>

                            <details className="pmc-form-section">
                                <summary>
                                    <i className="bi bi-geo-alt" />
                                    Address and descriptions
                                </summary>
                                <div className="d-grid gap-3 mt-3">
                                    <textarea
                                        className="form-control"
                                        rows={2}
                                        placeholder="Address"
                                        value={form.data.address}
                                        onChange={(event) =>
                                            form.setData(
                                                'address',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                    <textarea
                                        className="form-control"
                                        rows={3}
                                        placeholder="English description"
                                        value={form.data.description_en}
                                        onChange={(event) =>
                                            form.setData(
                                                'description_en',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                    <textarea
                                        className="form-control"
                                        rows={3}
                                        placeholder="Arabic description"
                                        value={form.data.description_ar}
                                        onChange={(event) =>
                                            form.setData(
                                                'description_ar',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                            </details>

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
                            createHref="/assets/create"
                            createLabel="Create asset"
                            rowHref={(asset) => `/assets/${asset.id}`}
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
                                                {asset.parent
                                                    ? ` · under ${asset.parent.title_en}`
                                                    : ''}
                                            </div>
                                            <div className="d-flex gap-2 mt-2 flex-wrap">
                                                <span className="pmc-chip">
                                                    {asset.code}
                                                </span>
                                                {asset.children_count ? (
                                                    <span className="pmc-chip pmc-chip--teal">
                                                        {asset.children_count}{' '}
                                                        children
                                                    </span>
                                                ) : null}
                                            </div>
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
                                            <div className="small text-secondary">
                                                {asset.level_label
                                                    ? `Level ${asset.level_label}`
                                                    : ''}
                                                {asset.unit_label
                                                    ? ` · Unit ${asset.unit_label}`
                                                    : ''}
                                            </div>
                                            {asset.rentable ? (
                                                <span className="pmc-chip pmc-chip--primary mt-2">
                                                    Rentable
                                                </span>
                                            ) : null}
                                        </>
                                    ),
                                },
                                {
                                    key: 'occupancy',
                                    label: 'Occupancy',
                                    render: (asset) => (
                                        <div className="d-grid gap-2">
                                            <span className="pmc-chip pmc-chip--primary">
                                                {asset.occupancy_status}
                                            </span>
                                            <span className="small text-secondary">
                                                {asset.status}
                                                {asset.active_leases_count
                                                    ? ` · ${asset.active_leases_count} active lease`
                                                    : ''}
                                            </span>
                                        </div>
                                    ),
                                },
                                {
                                    key: 'assignment',
                                    label: 'Owner / manager',
                                    render: (asset) => {
                                        const owner = primaryStakeholder(
                                            asset,
                                            'owner',
                                        );
                                        const manager = primaryStakeholder(
                                            asset,
                                            'manager',
                                        );

                                        return (
                                            <div className="pmc-assignment-cell">
                                                <span>
                                                    Owner:{' '}
                                                    <strong>
                                                        {owner ?? 'Unassigned'}
                                                    </strong>
                                                </span>
                                                <span>
                                                    Manager:{' '}
                                                    <strong>
                                                        {manager ??
                                                            'Unassigned'}
                                                    </strong>
                                                </span>
                                            </div>
                                        );
                                    },
                                },
                                {
                                    key: 'value',
                                    label: 'Value',
                                    render: (asset) => (
                                        <>
                                            <div>
                                                {currency(
                                                    asset.valuation_amount,
                                                    props.app.locale,
                                                    asset.currency,
                                                )}
                                            </div>
                                            {asset.area ? (
                                                <div className="small text-secondary">
                                                    {asset.area} sqm
                                                </div>
                                            ) : null}
                                        </>
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

function primaryStakeholder(
    asset: AssetRecord,
    relationshipType: 'owner' | 'manager',
): string | null {
    return (
        asset.stakeholders?.find(
            (item) => item.relationship_type === relationshipType,
        )?.user?.name ?? null
    );
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
