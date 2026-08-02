import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, dateTime } from '@/lib/utils';

import type { WorkOrderRecord } from './types';

type WorkOrderCellProps = { order: WorkOrderRecord };

export function WorkOrderIdentity({ order }: WorkOrderCellProps) {
    const { t } = useTranslator();

    return (
        <div className="pmc-primary-cell">
            <strong>{order.reference_code}</strong>
            <span>
                #{order.request?.id} {order.request?.title}
            </span>
            <div className="d-flex gap-1 flex-wrap">
                {order.request?.priority ? (
                    <StatusBadge
                        value={order.request.priority}
                        tone={
                            order.request.priority === 'urgent'
                                ? 'danger'
                                : 'neutral'
                        }
                    />
                ) : null}
                {order.is_overdue ? (
                    <StatusBadge
                        value="overdue"
                        tone="danger"
                        label={t('work_orders.overdue')}
                    />
                ) : null}
            </div>
        </div>
    );
}

export function WorkOrderPropertyTenant({ order }: WorkOrderCellProps) {
    const { locale, t } = useTranslator();
    const title =
        locale === 'ar'
            ? order.asset?.title_ar || order.asset?.title_en
            : order.asset?.title_en || order.asset?.title_ar;

    return (
        <div className="pmc-stacked-cell">
            <strong>{title ?? t('work_orders.no_property')}</strong>
            <span>
                {order.tenant?.name ?? t('work_orders.no_tenant')}
                {order.asset?.code ? ` · ${order.asset.code}` : ''}
            </span>
        </div>
    );
}

export function WorkOrderResponsibility({ order }: WorkOrderCellProps) {
    const { t } = useTranslator();

    return (
        <div className="pmc-stacked-cell">
            <strong>{order.vendor.name}</strong>
            <span>
                {order.assigned_to?.name ?? t('work_orders.no_internal_owner')}
            </span>
        </div>
    );
}

export function WorkOrderSchedule({ order }: WorkOrderCellProps) {
    const { locale, t } = useTranslator();

    return (
        <div className="pmc-stacked-cell">
            <StatusBadge value={order.status} />
            <strong>
                {order.scheduled_at
                    ? dateTime(order.scheduled_at, locale)
                    : t('work_orders.no_schedule')}
            </strong>
            <span>
                {order.tenant_access_required
                    ? t('work_orders.access_required')
                    : t('work_orders.no_access_required')}
            </span>
        </div>
    );
}

export function WorkOrderCosts({ order }: WorkOrderCellProps) {
    const { locale, t } = useTranslator();

    return (
        <div className="pmc-stacked-cell">
            <strong>
                {t('work_orders.estimated')}:{' '}
                {currency(order.estimated_amount ?? 0, locale, order.currency)}
            </strong>
            <span>
                {order.final_amount === null || order.final_amount === undefined
                    ? t('work_orders.no_final_cost')
                    : `${t('work_orders.final')}: ${currency(
                          order.final_amount,
                          locale,
                          order.currency,
                      )}`}
            </span>
        </div>
    );
}

export function WorkOrderActions({ order }: WorkOrderCellProps) {
    return (
        <RecordActions
            showHref={`/maintenance-work-orders/${order.id}`}
            editHref={`/maintenance-work-orders/${order.id}/edit`}
        />
    );
}
