import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { leaseMoveOutFilterFields } from './lease-move-out-filters';
import { useLeaseMoveOutTableConfig } from './lease-move-out-table-config';
import type { LeaseMoveOutPageProps } from './types';

type LeaseMoveOutTableProps = Pick<
    LeaseMoveOutPageProps,
    | 'moveOuts'
    | 'filters'
    | 'counts'
    | 'portfolioOptions'
    | 'propertyOptions'
    | 'queueOptions'
    | 'horizonOptions'
    | 'auth'
    | 'app'
>;

export function LeaseMoveOutTable(props: LeaseMoveOutTableProps) {
    const { t } = useTranslator();
    const table = useLeaseMoveOutTableConfig(props.app.locale);
    const filters = leaseMoveOutFilterFields(
        {
            queues: props.queueOptions,
            horizons: props.horizonOptions,
            portfolios: props.portfolioOptions,
            properties: props.propertyOptions,
            includePortfolio:
                props.auth.user?.roles.includes('superadmin') ?? false,
        },
        t,
    );

    return (
        <DataTable
            title={t('lease_move_outs.directory_title')}
            description={t('lease_move_outs.directory_description')}
            data={props.moveOuts}
            filters={props.filters}
            counts={props.counts}
            basePath="/lease-move-outs"
            rowHref={(moveOut) => `/leases/${moveOut.lease_id}`}
            exportHref={exportUrl('/exports/lease-move-outs', props.filters)}
            filterFields={filters}
            columns={table.columns}
            mobileCard={table.mobileCard}
            emptyText={t('lease_move_outs.empty')}
        />
    );
}
