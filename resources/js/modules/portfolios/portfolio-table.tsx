import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { usePortfolioFilterFields } from './portfolio-filters';
import { usePortfolioTableConfig } from './portfolio-table-config';
import type { PortfolioTableProps } from './types';

export function PortfolioTable(props: PortfolioTableProps) {
    const { t } = useTranslator();
    const filterFields = usePortfolioFilterFields(props.statusOptions);
    const table = usePortfolioTableConfig(props);

    return (
        <DataTable
            title={t('portfolios.directory_title')}
            description={t('portfolios.directory_description')}
            data={props.portfolios}
            filters={props.filters}
            counts={props.counts}
            basePath="/portfolios"
            rowHref={(portfolio) => `/portfolios/${portfolio.id}`}
            exportHref={exportUrl('/exports/portfolios', props.filters)}
            filterFields={filterFields}
            emptyText={t('portfolios.empty')}
            mobileCard={table.mobileCard}
            columns={table.columns}
        />
    );
}
