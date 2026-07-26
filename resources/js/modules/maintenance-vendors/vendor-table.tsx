import { ArchiveAction } from '@/components/archive-action';
import { DataTable } from '@/components/data-table';
import type {
    MobileTableConfig,
    TableColumn,
    TableFilterField,
} from '@/components/data-table';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';

import type {
    MaintenanceVendorIndexProps,
    MaintenanceVendorRecord,
} from './types';

export function MaintenanceVendorTable(props: MaintenanceVendorIndexProps) {
    const { t } = useTranslator();
    const contact = (vendor: MaintenanceVendorRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {vendor.contact_name ??
                    t('maintenance_vendors.no_contact_name')}
            </strong>
            <span>{vendor.phone ?? vendor.email ?? '-'}</span>
        </div>
    );
    const jobs = (vendor: MaintenanceVendorRecord) => (
        <div className="pmc-stacked-cell">
            <strong>{vendor.active_work_orders_count}</strong>
            <span>
                {t('maintenance_vendors.work_order_total', undefined, {
                    count: vendor.work_orders_count,
                })}
            </span>
        </div>
    );
    const status = (vendor: MaintenanceVendorRecord) => (
        <StatusBadge
            value={vendor.status}
            label={t(`status.${vendor.status}` as UiTranslationKey)}
        />
    );
    const actions = (vendor: MaintenanceVendorRecord) => (
        <RecordActions
            showHref={`/maintenance-vendors/${vendor.id}`}
            editHref={`/maintenance-vendors/${vendor.id}/edit`}
        >
            {vendor.status === 'active' ? (
                <ArchiveAction
                    href={`/maintenance-vendors/${vendor.id}`}
                    label={t('maintenance_vendors.archive')}
                    confirmMessage={t(
                        'maintenance_vendors.archive_confirm',
                        undefined,
                        { name: vendor.name },
                    )}
                />
            ) : null}
        </RecordActions>
    );
    const columns: Array<TableColumn<MaintenanceVendorRecord>> = [
        {
            key: 'vendor',
            label: t('maintenance_vendors.vendor'),
            render: (vendor) => (
                <div className="pmc-primary-cell">
                    <strong>{vendor.name}</strong>
                    <span>
                        {t(
                            `status.${vendor.service_category}` as UiTranslationKey,
                        )}
                    </span>
                </div>
            ),
        },
        {
            key: 'contact',
            label: t('maintenance_vendors.contact'),
            render: contact,
        },
        {
            key: 'work_orders',
            label: t('maintenance_vendors.work_orders'),
            render: jobs,
        },
        {
            key: 'status',
            label: t('maintenance_vendors.status'),
            render: status,
        },
        {
            key: 'actions',
            label: t('maintenance_vendors.actions'),
            className: 'text-end',
            render: actions,
        },
    ];
    const mobileCard: MobileTableConfig<MaintenanceVendorRecord> = {
        title: (vendor) => <strong>{vendor.name}</strong>,
        subtitle: (vendor) =>
            t(`status.${vendor.service_category}` as UiTranslationKey),
        status,
        meta: [
            {
                label: t('maintenance_vendors.contact'),
                value: contact,
            },
            {
                label: t('maintenance_vendors.work_orders'),
                value: jobs,
            },
        ],
        actions,
    };
    const filterFields: TableFilterField[] = [
        {
            name: 'status',
            label: t('maintenance_vendors.status'),
            options: [
                { label: t('maintenance_vendors.all'), value: 'all' },
                ...props.statusOptions.map((value) => ({
                    label: t(`status.${value}` as UiTranslationKey),
                    value,
                })),
            ],
        },
        {
            name: 'service_category',
            label: t('maintenance_vendors.category'),
            options: [
                { label: t('maintenance_vendors.all'), value: 'all' },
                ...props.categoryOptions.map((value) => ({
                    label: t(`status.${value}` as UiTranslationKey),
                    value,
                })),
            ],
        },
    ];

    if (props.auth.user?.roles.includes('superadmin')) {
        filterFields.push({
            name: 'portfolio_id',
            label: t('maintenance_vendors.portfolio'),
            options: [
                { label: t('maintenance_vendors.all'), value: 'all' },
                ...props.portfolioOptions.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return (
        <DataTable
            title={t('maintenance_vendors.directory')}
            description={t('maintenance_vendors.directory_description')}
            data={props.vendors}
            filters={props.filters}
            counts={props.counts}
            filterFields={filterFields}
            columns={columns}
            mobileCard={mobileCard}
            basePath="/maintenance-vendors"
            rowHref={(vendor) => `/maintenance-vendors/${vendor.id}`}
            createHref="/maintenance-vendors/create"
            createLabel={t('maintenance_vendors.create')}
            emptyText={t('maintenance_vendors.empty')}
        />
    );
}
