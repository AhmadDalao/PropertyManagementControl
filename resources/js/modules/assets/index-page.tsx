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
    stakeholders?: StakeholderRecord[];
    parent?: { title_en: string; title_ar?: string | null } | null;
    children_count?: number;
    active_leases_count?: number;
};

type AssetInsights = {
    total_assets: number;
    total_value: number;
    vacant_rentable_assets: number;
    occupied_assets: number;
    buildings: number;
    units: number;
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
};

export default function AssetsIndexPage() {
    const { props } = usePage<PageProps>();
    const { locale, t, text } = useTranslator();
    const assignmentGaps =
        props.insights.missing_owner + props.insights.missing_manager;
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
            options: [
                { label: 'All', value: 'all' },
                { label: 'Property', value: 'property' },
                { label: 'Building', value: 'building' },
                { label: 'Floor', value: 'floor' },
                { label: 'Unit', value: 'unit' },
                { label: 'Space', value: 'space' },
            ],
        },
        {
            name: 'usage_type',
            label: 'Usage',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Residential', value: 'residential' },
                { label: 'Commercial', value: 'commercial' },
                { label: 'Mixed', value: 'mixed' },
                { label: 'Personal', value: 'personal' },
            ],
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
            <Head title={text('Properties & Units')} />

            <WorkspaceHeader
                eyebrow="Portfolio"
                title="Properties & units"
                description="Find a building, floor, unit, or space. Open the record for ownership, leases, documents, maintenance, and history."
                actions={[
                    {
                        label: 'Property map',
                        href: '/property-map',
                        icon: 'bi-map',
                    },
                    {
                        label: 'Create asset',
                        href: '/assets/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Assets',
                        value: props.insights.total_assets,
                        detail: t('assets.mix', undefined, {
                            buildings: props.insights.buildings,
                            units: props.insights.units,
                        }),
                        icon: 'bi-buildings',
                        tone: 'ink',
                    },
                    {
                        label: 'Portfolio value',
                        value: currency(
                            props.insights.total_value,
                            props.app.locale,
                        ),
                        detail: t('assets.recorded_valuation'),
                        icon: 'bi-bank',
                        tone: 'blue',
                    },
                    {
                        label: 'Occupancy',
                        value: `${props.insights.rentable_occupancy_rate}%`,
                        detail: t('assets.vacant_rentable', undefined, {
                            count: props.insights.vacant_rentable_assets,
                        }),
                        icon: 'bi-house-check',
                        tone: 'teal',
                    },
                    {
                        label: 'Assignment gaps',
                        value: assignmentGaps,
                        detail: t('assets.assignment_gaps', undefined, {
                            owners: props.insights.missing_owner,
                            managers: props.insights.missing_manager,
                        }),
                        icon: 'bi-person-exclamation',
                        tone: assignmentGaps > 0 ? 'red' : 'amber',
                    },
                ]}
            />

            <DataTable
                title="Asset register"
                description="Search title, code, parent, address, owner, manager, zone, or land number."
                data={props.assets}
                filters={props.filters}
                counts={props.counts}
                basePath="/assets"
                rowHref={(asset) => `/assets/${asset.id}`}
                exportHref={exportUrl('/exports/assets', props.filters)}
                filterFields={filterFields}
                columns={[
                    {
                        key: 'asset',
                        label: 'Asset',
                        render: (asset) => (
                            <div className="pmc-primary-cell">
                                <strong>
                                    {locale === 'ar'
                                        ? asset.title_ar || asset.title_en
                                        : asset.title_en || asset.title_ar}
                                </strong>
                                <span>
                                    {asset.code}
                                    {asset.parent
                                        ? ` · ${
                                              locale === 'ar'
                                                  ? asset.parent.title_ar ||
                                                    asset.parent.title_en
                                                  : asset.parent.title_en ||
                                                    asset.parent.title_ar
                                          }`
                                        : ''}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'type',
                        label: 'Type',
                        render: (asset) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {text(humanLabel(asset.asset_type))}
                                </strong>
                                <span>
                                    {text(humanLabel(asset.usage_type))}
                                    {asset.level_label
                                        ? ` · ${t('assets.level', undefined, {
                                              level: asset.level_label,
                                          })}`
                                        : ''}
                                    {asset.unit_label
                                        ? ` · ${asset.unit_label}`
                                        : ''}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'occupancy',
                        label: 'Occupancy',
                        render: (asset) => (
                            <div className="pmc-badge-stack">
                                <StatusBadge
                                    value={asset.occupancy_status}
                                    tone={
                                        asset.occupancy_status === 'occupied'
                                            ? 'success'
                                            : asset.occupancy_status ===
                                                'maintenance'
                                              ? 'danger'
                                              : 'warning'
                                    }
                                />
                                {asset.rentable ? (
                                    <span>{text('Rentable')}</span>
                                ) : (
                                    <span>{text('Not rentable')}</span>
                                )}
                            </div>
                        ),
                    },
                    {
                        key: 'assignment',
                        label: 'Owner / manager',
                        render: (asset) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {primaryStakeholder(asset, 'owner') ??
                                        text('Owner not assigned')}
                                </strong>
                                <span>
                                    {primaryStakeholder(asset, 'manager') ??
                                        text('Manager not assigned')}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'value',
                        label: 'Value',
                        render: (asset) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {currency(
                                        asset.valuation_amount,
                                        props.app.locale,
                                        asset.currency,
                                    )}
                                </strong>
                                <span>
                                    {asset.area
                                        ? t('assets.area_sqm', undefined, {
                                              area: asset.area,
                                          })
                                        : text('Area not set')}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'actions',
                        label: 'Actions',
                        className: 'text-end',
                        render: (asset) => (
                            <RecordActions
                                showHref={`/assets/${asset.id}`}
                                editHref={`/assets/${asset.id}/edit`}
                            >
                                {asset.status !== 'archived' ? (
                                    <ArchiveAction
                                        href={`/assets/${asset.id}`}
                                        confirmMessage={t(
                                            'assets.archive_confirm',
                                            undefined,
                                            {
                                                name:
                                                    locale === 'ar'
                                                        ? asset.title_ar ||
                                                          asset.title_en
                                                        : asset.title_en ||
                                                          asset.title_ar,
                                            },
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

function primaryStakeholder(
    asset: AssetRecord,
    relationshipType: 'owner' | 'manager',
) {
    return (
        asset.stakeholders?.find(
            (item) => item.relationship_type === relationshipType,
        )?.user?.name ?? null
    );
}
