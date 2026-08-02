import { DataTable } from '@/components/data-table';
import type {
    MobileTableConfig,
    TableColumn,
    TableFilterField,
} from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import {
    AgingActions,
    AgingBucket,
    DueAge,
    FollowUp,
    Installment,
    Outstanding,
    PropertySpace,
    TenantLease,
} from './arrears-aging-cells';
import type {
    ArrearsAgingPageProps,
    ArrearsAgingRecord,
} from './arrears-aging-types';

export function ArrearsAgingRecords({
    props,
}: {
    props: ArrearsAgingPageProps;
}) {
    const { locale, t } = useTranslator();
    const columns: Array<TableColumn<ArrearsAgingRecord>> = [
        {
            key: 'tenant',
            label: t('reports.aging_tenant'),
            render: (record) => <TenantLease record={record} />,
        },
        {
            key: 'property',
            label: t('reports.aging_property_space'),
            render: (record) => <PropertySpace record={record} />,
        },
        {
            key: 'installment',
            label: t('reports.aging_lease_installment'),
            render: (record) => <Installment record={record} />,
        },
        {
            key: 'due',
            label: t('reports.aging_due_age'),
            render: (record) => <DueAge record={record} />,
        },
        {
            key: 'outstanding',
            label: t('reports.aging_outstanding'),
            className: 'pmc-table-number',
            render: (record) => <Outstanding record={record} />,
        },
        {
            key: 'follow_up',
            label: t('reports.aging_follow_up'),
            render: (record) => <FollowUp record={record} />,
        },
        {
            key: 'actions',
            label: t('common.actions'),
            render: (record) => <AgingActions record={record} />,
        },
    ];
    const filterFields: TableFilterField[] = [
        {
            name: 'bucket',
            label: t('reports.aging_bucket'),
            options: props.bucketOptions.map((bucket) => ({
                value: bucket,
                label: t(`reports.aging_bucket_${bucket}`),
            })),
        },
        {
            name: 'portfolio_id',
            label: t('reports.portfolio'),
            clears: ['property_id'],
            options: [
                { value: 'all', label: t('reports.all_portfolios') },
                ...props.portfolioOptions.map((portfolio) => ({
                    value: portfolio.id,
                    label: portfolio.name,
                })),
            ],
        },
        {
            name: 'property_id',
            label: t('reports.property'),
            options: [
                { value: 'all', label: t('reports.all_properties') },
                ...props.propertyOptions.map((property) => ({
                    value: property.id,
                    label: property.name,
                })),
            ],
        },
    ];
    const mobile: MobileTableConfig<ArrearsAgingRecord> = {
        title: (record) => <TenantLease record={record} />,
        subtitle: (record) => <PropertySpace record={record} />,
        status: (record) => <AgingBucket record={record} />,
        meta: [
            {
                label: t('reports.aging_outstanding'),
                value: (record) =>
                    currency(
                        record.outstanding_amount,
                        locale,
                        record.currency,
                    ),
            },
            {
                label: t('rent_collection.due_date'),
                value: (record) => humanDate(record.due_date, locale),
            },
            {
                label: t('reports.aging_follow_up'),
                value: (record) =>
                    t(
                        `rent_collection.follow_up_state_${record.follow_up.state}`,
                    ),
            },
        ],
        actions: (record) => <AgingActions record={record} />,
    };

    return (
        <DataTable
            title={t('reports.aging_records_title')}
            description={t('reports.aging_records_description')}
            data={props.records}
            columns={columns}
            filters={props.filters}
            basePath="/reports/arrears-aging"
            exportHref={props.downloads.xlsx}
            counts={props.counts}
            filterFields={filterFields}
            emptyText={t('reports.aging_no_matches')}
            rowHref={(record) => record.links.follow_up}
            mobileCard={mobile}
        />
    );
}
