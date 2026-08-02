import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { UserRecord, UserTableProps } from './types';
import { useUserTableCells } from './user-table-cells';

export function useUserTableConfig(props: UserTableProps) {
    const { locale, t } = useTranslator();
    const cells = useUserTableCells(props);
    const mobileCard: MobileTableConfig<UserRecord> = {
        title: (user) => user.name,
        subtitle: (user) => user.email,
        status: (user) => <StatusBadge value={user.status} />,
        meta: [
            { label: t('users.role'), value: cells.roleCell },
            {
                label: t('users.portfolio'),
                value: (user) =>
                    (locale === 'ar'
                        ? user.portfolio?.name_ar || user.portfolio?.name_en
                        : user.portfolio?.name_en || user.portfolio?.name_ar) ??
                    t('users.global_account'),
            },
            {
                label: t('users.open_workload'),
                value: (user) =>
                    t('users.assignment_count', undefined, {
                        count: user.open_assignments_count ?? 0,
                    }),
            },
        ],
        actions: cells.actions,
    };
    const columns: TableColumn<UserRecord>[] = [
        { key: 'user', label: t('users.user'), render: cells.userCell },
        { key: 'role', label: t('users.role'), render: cells.roleCell },
        {
            key: 'portfolio',
            label: t('users.portfolio'),
            render: cells.portfolioCell,
        },
        {
            key: 'account',
            label: t('users.portal_access'),
            render: cells.accessCell,
        },
        {
            key: 'actions',
            label: t('users.actions'),
            className: 'text-end',
            render: cells.actions,
        },
    ];

    return { ...cells, mobileCard, columns };
}
