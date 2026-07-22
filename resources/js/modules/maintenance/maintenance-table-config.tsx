import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import {
    MaintenanceActions,
    MaintenanceAssignment,
    MaintenanceAssetTenant,
    MaintenanceIdentity,
    MaintenancePriority,
    MaintenanceStatusDue,
} from './maintenance-table-cells';
import type { MaintenanceRecord, MaintenanceTableProps } from './types';

export function useMaintenanceTableConfig(props: MaintenanceTableProps): {
    columns: Array<TableColumn<MaintenanceRecord>>;
    mobileCard: MobileTableConfig<MaintenanceRecord>;
} {
    const { t } = useTranslator();
    const identity = (request: MaintenanceRecord) => (
        <MaintenanceIdentity request={request} />
    );
    const assetTenant = (request: MaintenanceRecord) => (
        <MaintenanceAssetTenant request={request} />
    );
    const assignment = (request: MaintenanceRecord) => (
        <MaintenanceAssignment request={request} {...props} />
    );
    const actions = (request: MaintenanceRecord) => (
        <MaintenanceActions request={request} mode={props.mode} />
    );

    return {
        mobileCard: {
            title: identity,
            subtitle: (request) => <MaintenancePriority request={request} />,
            status: (request) => <MaintenanceStatusDue request={request} />,
            meta: [
                { label: t('maintenance.asset_tenant'), value: assetTenant },
                { label: t('maintenance.assignment'), value: assignment },
            ],
            actions,
        },
        columns: [
            {
                key: 'request',
                label: t('maintenance.request'),
                render: identity,
            },
            {
                key: 'asset-tenant',
                label: t('maintenance.asset_tenant'),
                render: assetTenant,
            },
            {
                key: 'assignment',
                label: t('maintenance.assignment'),
                render: assignment,
            },
            {
                key: 'priority',
                label: t('maintenance.priority'),
                render: (request) => <MaintenancePriority request={request} />,
            },
            {
                key: 'status',
                label: t('maintenance.status'),
                render: (request) => <MaintenanceStatusDue request={request} />,
            },
            {
                key: 'actions',
                label: t('maintenance.actions'),
                className: 'text-end',
                render: actions,
            },
        ],
    };
}
