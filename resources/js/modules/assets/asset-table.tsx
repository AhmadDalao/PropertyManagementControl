import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { useAssetFilterFields } from './asset-filters';
import { useAssetTableConfig } from './asset-table-config';
import type { AssetTableProps } from './types';

export function AssetTable(props: AssetTableProps) {
    const { t } = useTranslator();
    const filterFields = useAssetFilterFields({
        portfolioOptions: props.portfolioOptions,
        includePortfolio:
            props.auth.user?.roles.includes('superadmin') ?? false,
    });
    const table = useAssetTableConfig(props);

    return (
        <DataTable
            title={t('assets.directory_title')}
            description={t('assets.directory_description')}
            data={props.assets}
            filters={props.filters}
            counts={props.counts}
            basePath="/assets"
            rowHref={(asset) => `/assets/${asset.id}`}
            exportHref={exportUrl('/exports/assets', props.filters)}
            filterFields={filterFields}
            emptyText={t('assets.empty')}
            createHref="/assets/create"
            createLabel={t('assets.create_asset')}
            mobileCard={table.mobileCard}
            columns={table.columns}
        />
    );
}
