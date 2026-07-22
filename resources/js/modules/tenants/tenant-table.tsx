import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { useTenantFilterFields } from './tenant-filters';
import { useTenantTableConfig } from './tenant-table-config';
import type { TenantTableProps } from './types';

export function TenantTable(props: TenantTableProps) {
    const { t } = useTranslator();
    const table = useTenantTableConfig();
    const filterFields = useTenantFilterFields({
        statuses: props.statusOptions,
        profileTypes: props.profileTypeOptions,
        portfolios: props.portfolioOptions,
        includePortfolio:
            props.auth.user?.roles.includes('superadmin') ?? false,
    });

    return (
        <DataTable
            title={t('tenants.directory_title')}
            description={t('tenants.directory_description')}
            data={props.tenants}
            filters={props.filters}
            counts={props.counts}
            basePath="/tenants"
            rowHref={(tenant) => `/tenants/${tenant.id}`}
            exportHref={exportUrl('/exports/tenants', props.filters)}
            filterFields={filterFields}
            emptyText={t('tenants.empty')}
            createHref="/tenants/create"
            createLabel={t('tenants.create_tenant')}
            mobileCard={table.mobileCard}
            columns={table.columns}
        />
    );
}
