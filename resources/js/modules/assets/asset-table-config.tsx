import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import {
    AssetActions,
    AssetAssignment,
    AssetIdentity,
    AssetOccupancy,
    AssetType,
    AssetValue,
} from './asset-table-cells';
import type { AssetRecord, AssetTableProps } from './types';

export function useAssetTableConfig(props: AssetTableProps): {
    columns: Array<TableColumn<AssetRecord>>;
    mobileCard: MobileTableConfig<AssetRecord>;
} {
    const { t } = useTranslator();
    const identity = (asset: AssetRecord) => <AssetIdentity asset={asset} />;
    const type = (asset: AssetRecord) => <AssetType asset={asset} />;
    const occupancy = (asset: AssetRecord) => <AssetOccupancy asset={asset} />;
    const assignment = (asset: AssetRecord) => (
        <AssetAssignment asset={asset} />
    );
    const value = (asset: AssetRecord) => (
        <AssetValue asset={asset} app={props.app} />
    );
    const actions = (asset: AssetRecord) => <AssetActions asset={asset} />;

    return {
        mobileCard: {
            title: identity,
            subtitle: type,
            status: occupancy,
            meta: [
                { label: t('assets.assignment'), value: assignment },
                { label: t('assets.value'), value },
            ],
            actions,
        },
        columns: [
            { key: 'asset', label: t('assets.asset'), render: identity },
            { key: 'type', label: t('assets.type'), render: type },
            {
                key: 'occupancy',
                label: t('assets.occupancy'),
                render: occupancy,
            },
            {
                key: 'assignment',
                label: t('assets.assignment'),
                render: assignment,
            },
            { key: 'value', label: t('assets.value'), render: value },
            {
                key: 'actions',
                label: t('assets.actions'),
                className: 'text-end',
                render: actions,
            },
        ],
    };
}
