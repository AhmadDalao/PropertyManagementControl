import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { useTenantTableCells } from './tenant-table-cells';
import type { TenantRecord } from './types';

export function useTenantTableConfig() {
    const { t } = useTranslator();
    const cells = useTenantTableCells();
    const mobileCard: MobileTableConfig<TenantRecord> = {
        title: cells.tenantCell,
        subtitle: cells.profileCell,
        status: (tenant) => <StatusBadge value={tenant.status} />,
        meta: [
            {
                label: t('tenants.rental_activity'),
                value: cells.rentalCell,
            },
            {
                label: t('tenants.portal_account'),
                value: (tenant) => (
                    <StatusBadge value={tenant.user?.status ?? 'inactive'} />
                ),
            },
        ],
        actions: cells.actions,
    };
    const columns: TableColumn<TenantRecord>[] = [
        {
            key: 'tenant',
            label: t('tenants.tenant'),
            render: cells.tenantCell,
        },
        {
            key: 'profile',
            label: t('tenants.profile'),
            render: cells.profileCell,
        },
        {
            key: 'leases',
            label: t('tenants.rental_activity'),
            render: cells.rentalCell,
        },
        {
            key: 'status',
            label: t('tenants.status'),
            render: (tenant) => <StatusBadge value={tenant.status} />,
        },
        {
            key: 'actions',
            label: t('tenants.actions'),
            className: 'text-end',
            render: cells.actions,
        },
    ];

    return { ...cells, mobileCard, columns };
}
