import type { MobileTableConfig } from '@/components/data-table';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { dateTime } from '@/lib/utils';

import type { WorkOrderRecord } from './types';
import { WorkOrderActions } from './work-order-cells';

export function useWorkOrderMobileCard(): MobileTableConfig<WorkOrderRecord> {
    const { locale, t } = useTranslator();

    return {
        title: (order) => order.reference_code,
        subtitle: (order) =>
            `#${order.request?.id ?? order.id} ${order.request?.title ?? ''}`.trim(),
        status: (order) => (
            <div className="pmc-inline-badges">
                <StatusBadge value={order.status} />
                {order.is_overdue ? (
                    <StatusBadge
                        value="overdue"
                        tone="danger"
                        label={t('work_orders.overdue')}
                    />
                ) : null}
            </div>
        ),
        meta: [
            {
                label: t('work_orders.property_tenant'),
                value: (order) =>
                    [
                        locale === 'ar'
                            ? order.asset?.title_ar || order.asset?.title_en
                            : order.asset?.title_en || order.asset?.title_ar,
                        order.tenant?.name,
                    ]
                        .filter(Boolean)
                        .join(' · ') || t('work_orders.no_property'),
            },
            {
                label: t('work_orders.responsibility'),
                value: (order) =>
                    [
                        order.vendor.name,
                        order.assigned_to?.name ??
                            t('work_orders.no_internal_owner'),
                    ].join(' · '),
            },
            {
                label: t('work_orders.schedule_access'),
                value: (order) =>
                    order.scheduled_at
                        ? dateTime(order.scheduled_at, locale)
                        : t('work_orders.no_schedule'),
            },
        ],
        actions: (order) => <WorkOrderActions order={order} />,
    };
}
