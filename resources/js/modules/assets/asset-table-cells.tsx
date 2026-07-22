import { ArchiveAction } from '@/components/archive-action';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import type { AssetRecord, AssetTableProps } from './types';

type AssetCellProps = { asset: AssetRecord };

export function AssetIdentity({ asset }: AssetCellProps) {
    const { locale } = useTranslator();
    const parent = asset.parent ? localizedParent(asset, locale) : null;

    return (
        <div className="pmc-primary-cell">
            <strong>{localizedAssetTitle(asset, locale)}</strong>
            <span>{[asset.code, parent].filter(Boolean).join(' · ')}</span>
        </div>
    );
}

export function AssetType({ asset }: AssetCellProps) {
    const { t } = useTranslator();

    return (
        <div className="pmc-stacked-cell">
            <strong>{t(`assets.types.${asset.asset_type}`)}</strong>
            <span>
                {t(`assets.usages.${asset.usage_type}`)}
                {asset.level_label
                    ? ` · ${t('assets.level', undefined, {
                          level: asset.level_label,
                      })}`
                    : ''}
                {asset.unit_label ? ` · ${asset.unit_label}` : ''}
            </span>
        </div>
    );
}

export function AssetOccupancy({ asset }: AssetCellProps) {
    const { t } = useTranslator();

    return (
        <div className="pmc-badge-stack">
            <StatusBadge
                value={asset.occupancy_status}
                tone={
                    asset.occupancy_status === 'occupied'
                        ? 'success'
                        : asset.occupancy_status === 'maintenance'
                          ? 'danger'
                          : 'warning'
                }
            />
            <span>
                {t(asset.rentable ? 'assets.rentable' : 'assets.not_rentable')}
            </span>
        </div>
    );
}

export function AssetAssignment({ asset }: AssetCellProps) {
    const { t } = useTranslator();

    return (
        <div className="pmc-stacked-cell">
            <strong>
                {primaryStakeholder(asset, 'owner') ??
                    t('assets.owner_not_assigned')}
            </strong>
            <span>
                {primaryStakeholder(asset, 'manager') ??
                    t('assets.manager_not_assigned')}
            </span>
        </div>
    );
}

export function AssetValue({
    asset,
    app,
}: AssetCellProps & Pick<AssetTableProps, 'app'>) {
    const { t } = useTranslator();

    return (
        <div className="pmc-stacked-cell">
            <strong>
                {currency(asset.valuation_amount, app.locale, asset.currency)}
            </strong>
            <span>
                {asset.area
                    ? t('assets.area_sqm', undefined, { area: asset.area })
                    : t('assets.area_not_set')}
            </span>
        </div>
    );
}

export function AssetActions({ asset }: AssetCellProps) {
    const { locale, t } = useTranslator();

    return (
        <RecordActions
            showHref={`/assets/${asset.id}`}
            editHref={`/assets/${asset.id}/edit`}
        >
            {asset.status !== 'archived' ? (
                <ArchiveAction
                    href={`/assets/${asset.id}`}
                    confirmMessage={t('assets.archive_confirm', undefined, {
                        name: localizedAssetTitle(asset, locale),
                    })}
                />
            ) : null}
        </RecordActions>
    );
}

export function localizedAssetTitle(asset: AssetRecord, locale: string) {
    return locale === 'ar'
        ? asset.title_ar || asset.title_en
        : asset.title_en || asset.title_ar;
}

function localizedParent(asset: AssetRecord, locale: string) {
    return locale === 'ar'
        ? asset.parent?.title_ar || asset.parent?.title_en
        : asset.parent?.title_en || asset.parent?.title_ar;
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
