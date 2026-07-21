import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import {
    RecordActions,
    StatusBadge,
    humanLabel,
} from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import { assetFilterFields } from './asset-filters';
import type { AssetIndexPageProps, AssetRecord } from './types';

type AssetTableProps = Pick<
    AssetIndexPageProps,
    'assets' | 'filters' | 'counts' | 'portfolioOptions' | 'auth' | 'app'
>;

export function AssetTable(props: AssetTableProps) {
    const { locale, t, text } = useTranslator();
    const filters = assetFilterFields(
        props.portfolioOptions,
        props.auth.user?.roles.includes('superadmin') ?? false,
    );

    return (
        <DataTable
            title="Asset register"
            description="Search title, code, parent, address, owner, manager, zone, or land number."
            data={props.assets}
            filters={props.filters}
            counts={props.counts}
            basePath="/assets"
            rowHref={(asset) => `/assets/${asset.id}`}
            exportHref={exportUrl('/exports/assets', props.filters)}
            filterFields={filters}
            columns={[
                {
                    key: 'asset',
                    label: 'Asset',
                    render: (asset) => (
                        <div className="pmc-primary-cell">
                            <strong>{localizedTitle(asset, locale)}</strong>
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
                            <span>
                                {text(
                                    asset.rentable
                                        ? 'Rentable'
                                        : 'Not rentable',
                                )}
                            </span>
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
                                        { name: localizedTitle(asset, locale) },
                                    )}
                                />
                            ) : null}
                        </RecordActions>
                    ),
                },
            ]}
        />
    );
}

function localizedTitle(asset: AssetRecord, locale: string) {
    return locale === 'ar'
        ? asset.title_ar || asset.title_en
        : asset.title_en || asset.title_ar;
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
