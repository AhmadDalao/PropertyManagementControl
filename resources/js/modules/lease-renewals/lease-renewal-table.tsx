import { DataTable } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { leaseRenewalDownloads } from './lease-renewal-downloads';
import { leaseRenewalFilterFields } from './lease-renewal-filters';
import { useLeaseRenewalTableConfig } from './lease-renewal-table-config';
import type { LeaseRenewalPageProps } from './types';

type LeaseRenewalTableProps = Pick<
    LeaseRenewalPageProps,
    | 'renewals'
    | 'filters'
    | 'counts'
    | 'portfolioOptions'
    | 'propertyOptions'
    | 'queueOptions'
    | 'horizonOptions'
    | 'leaseStatusOptions'
    | 'downloads'
    | 'auth'
    | 'app'
>;

export function LeaseRenewalTable(props: LeaseRenewalTableProps) {
    const { t } = useTranslator();
    const table = useLeaseRenewalTableConfig(props.app.locale);
    const filters = leaseRenewalFilterFields(
        {
            queues: props.queueOptions,
            horizons: props.horizonOptions,
            leaseStatuses: props.leaseStatusOptions,
            portfolios: props.portfolioOptions,
            properties: props.propertyOptions,
            includePortfolio:
                props.auth.user?.roles.includes('superadmin') ?? false,
        },
        t,
    );

    return (
        <DataTable
            title={t('lease_renewals.directory_title')}
            description={t('lease_renewals.directory_description')}
            data={props.renewals}
            filters={props.filters}
            counts={props.counts}
            basePath="/lease-renewals"
            rowHref={(lease) => `/leases/${lease.id}`}
            exportLinks={leaseRenewalDownloads(props.downloads, t)}
            filterFields={filters}
            columns={table.columns}
            mobileCard={table.mobileCard}
            emptyText={t('lease_renewals.empty')}
        />
    );
}
