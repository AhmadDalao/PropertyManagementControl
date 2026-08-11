import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { NotificationIndexPageProps } from './types';

type NotificationMetricsProps = Pick<
    NotificationIndexPageProps,
    'counts' | 'typeCounts'
>;

export function NotificationMetrics({
    counts,
    typeCounts,
}: NotificationMetricsProps) {
    const { t } = useTranslator();

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('notifications.all'),
                    value: counts.all,
                    detail: t('notifications.all_detail'),
                    icon: 'bi-envelope',
                    tone: 'ink',
                },
                {
                    label: t('notifications.unread'),
                    value: counts.unread,
                    detail: t('notifications.unread_detail'),
                    icon: 'bi-envelope',
                    tone: 'red',
                },
                {
                    label: t('notifications.types.maintenance_request'),
                    value: typeCounts.maintenance_request,
                    detail: t('notifications.type_detail'),
                    icon: 'bi-tools',
                    tone: 'amber',
                },
                {
                    label: t('notifications.types.payment'),
                    value: typeCounts.payment,
                    detail: t('notifications.type_detail'),
                    icon: 'bi-cash-stack',
                    tone: 'teal',
                },
                {
                    label: t('notifications.types.lease'),
                    value: typeCounts.lease,
                    detail: t('notifications.type_detail'),
                    icon: 'bi-file-earmark-text',
                    tone: 'blue',
                },
            ]}
        />
    );
}
