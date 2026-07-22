import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import type { UserTableProps } from './types';
import { useUserFilterFields } from './user-filters';
import { useUserTableConfig } from './user-table-config';

export function UserTable(props: UserTableProps) {
    const { t } = useTranslator();
    const table = useUserTableConfig(props);
    const filterFields = useUserFilterFields({
        statuses: props.statusOptions,
        roles: props.roleOptions,
        portfolios: props.portfolioOptions,
        includePortfolio:
            props.auth.user?.roles.includes('superadmin') ?? false,
    });

    return (
        <DataTable
            title={t('users.directory_title')}
            description={t('users.directory_description')}
            data={props.users}
            filters={props.filters}
            counts={props.counts}
            basePath="/users"
            rowHref={table.userHref}
            exportHref={exportUrl('/exports/users', props.filters)}
            filterFields={filterFields}
            emptyText={t('users.empty')}
            createHref="/users/create"
            createLabel={t('users.create_user')}
            mobileCard={table.mobileCard}
            columns={table.columns}
        />
    );
}
