import { useTranslator } from '@/lib/i18n';
import { dateTime } from '@/lib/utils';

import type { ActionCenterItem } from './types';

export function ActionCenterWorkOrderContext({
    workOrder,
}: {
    workOrder: NonNullable<ActionCenterItem['work_order']>;
}) {
    const { locale, t } = useTranslator();

    return (
        <>
            <div>
                <dt>
                    <i className="bi bi-clipboard2-check" aria-hidden="true" />
                    {t('action_center.work_order')}
                </dt>
                <dd>
                    {workOrder.reference_code} · {workOrder.vendor_name}
                </dd>
            </div>
            {workOrder.scheduled_on ? (
                <div>
                    <dt>
                        <i
                            className="bi bi-calendar-event"
                            aria-hidden="true"
                        />
                        {t('action_center.service_visit')}
                    </dt>
                    <dd>{dateTime(workOrder.scheduled_on, locale)}</dd>
                </div>
            ) : null}
        </>
    );
}
