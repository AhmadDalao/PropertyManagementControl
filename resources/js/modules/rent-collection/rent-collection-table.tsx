import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { rentCollectionFilterFields } from './rent-collection-filters';
import { useRentCollectionTableConfig } from './rent-collection-table-config';
import type { RentCollectionPageProps } from './types';

type RentCollectionTableProps = Pick<
    RentCollectionPageProps,
    | 'installments'
    | 'filters'
    | 'counts'
    | 'portfolioOptions'
    | 'propertyOptions'
    | 'statusOptions'
    | 'lineTypeOptions'
    | 'followUpOptions'
    | 'auth'
    | 'app'
>;

export function RentCollectionTable(props: RentCollectionTableProps) {
    const { t } = useTranslator();
    const table = useRentCollectionTableConfig(props.app.locale);
    const filters = rentCollectionFilterFields(
        {
            statuses: props.statusOptions,
            lineTypes: props.lineTypeOptions,
            followUps: props.followUpOptions,
            portfolios: props.portfolioOptions,
            properties: props.propertyOptions,
            includePortfolio:
                props.auth.user?.roles.includes('superadmin') ?? false,
        },
        t,
    );

    return (
        <DataTable
            title={t('rent_collection.ledger_title')}
            description={t('rent_collection.ledger_description')}
            data={props.installments}
            filters={props.filters}
            counts={props.counts}
            basePath="/rent-collection"
            rowHref={(installment) =>
                `/rent-collection/${installment.id}/follow-up`
            }
            exportHref={exportUrl('/exports/rent-collection', props.filters)}
            filterFields={filters}
            columns={table.columns}
            mobileCard={table.mobileCard}
            emptyText={t('rent_collection.empty')}
        />
    );
}
