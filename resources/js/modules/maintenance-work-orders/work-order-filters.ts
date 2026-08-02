import { propertyFilterField } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';

import type { WorkOrderIndexProps } from './types';

export function useWorkOrderFilters(
    props: WorkOrderIndexProps,
): TableFilterField[] {
    const { t } = useTranslator();
    const all = { label: t('work_orders.all'), value: 'all' };
    const fields: TableFilterField[] = [
        {
            name: 'status',
            label: t('work_orders.status'),
            options: [
                all,
                ...props.statusOptions.map((value) => ({
                    label: t(`status.${value}` as UiTranslationKey),
                    value,
                })),
            ],
        },
        {
            name: 'schedule',
            label: t('work_orders.schedule_filter'),
            options: [
                all,
                ...props.scheduleOptions.map((value) => ({
                    label: t(
                        `work_orders.schedule_${value}` as UiTranslationKey,
                    ),
                    value,
                })),
            ],
        },
        {
            name: 'tenant_access',
            label: t('work_orders.tenant_access_filter'),
            options: [
                all,
                ...props.tenantAccessOptions.map((value) => ({
                    label: t(
                        `work_orders.tenant_access_${value}` as UiTranslationKey,
                    ),
                    value,
                })),
            ],
        },
        {
            name: 'vendor_id',
            label: t('work_orders.vendor'),
            options: [
                { label: t('work_orders.all_vendors'), value: 'all' },
                ...props.vendorOptions.map((vendor) => ({
                    label: vendor.name,
                    value: vendor.id,
                })),
            ],
        },
        {
            name: 'assigned_to_user_id',
            label: t('work_orders.internal_owner'),
            options: [
                { label: t('work_orders.all_assignees'), value: 'all' },
                ...props.assigneeOptions.map((user) => ({
                    label: user.name,
                    value: user.id,
                })),
            ],
        },
        { name: 'date_from', label: t('work_orders.from'), type: 'date' },
        { name: 'date_to', label: t('work_orders.to'), type: 'date' },
    ];

    if (props.auth.user?.roles.includes('superadmin')) {
        fields.push({
            name: 'portfolio_id',
            label: t('work_orders.portfolio'),
            clears: ['property_id'],
            options: [
                { label: t('work_orders.all_portfolios'), value: 'all' },
                ...props.portfolioOptions.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    fields.push(propertyFilterField(props.propertyOptions, t));

    return fields;
}
