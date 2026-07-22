import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { leaseFilterFields } from './lease-filters';
import { useLeaseTableConfig } from './lease-table-config';
import type { LeaseIndexPageProps } from './types';

type LeaseTableProps = Pick<
    LeaseIndexPageProps,
    | 'leases'
    | 'filters'
    | 'counts'
    | 'portfolioOptions'
    | 'statusOptions'
    | 'frequencyOptions'
    | 'auth'
    | 'app'
>;

export function LeaseTable(props: LeaseTableProps) {
    const { t } = useTranslator();
    const table = useLeaseTableConfig(props.app.locale);
    const filters = leaseFilterFields(
        {
            statuses: props.statusOptions,
            frequencies: props.frequencyOptions,
            portfolios: props.portfolioOptions,
            includePortfolio:
                props.auth.user?.roles.includes('superadmin') ?? false,
        },
        t,
    );

    return (
        <DataTable
            title={t('leases.register_title')}
            description={t('leases.register_description')}
            data={props.leases}
            filters={props.filters}
            counts={props.counts}
            basePath="/leases"
            rowHref={(lease) => `/leases/${lease.id}`}
            exportHref={exportUrl('/exports/leases', props.filters)}
            filterFields={filters}
            columns={table.columns}
            mobileCard={table.mobileCard}
            emptyText={t('leases.empty')}
        />
    );
}
