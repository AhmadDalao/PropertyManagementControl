import { Head, usePage } from '@inertiajs/react';

import {
    MetricGrid,
    WorkspaceHeader,
    WorkspacePanel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { NotificationFilters } from './notification-filters';
import { NotificationList } from './notification-list';
import type { NotificationIndexPageProps } from './types';

export default function NotificationsIndexPage() {
    const { props } = usePage<NotificationIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('notifications.title')} />

            <WorkspaceHeader
                eyebrow={t('notifications.eyebrow')}
                title={t('notifications.title')}
                description={t('notifications.description')}
            />

            <MetricGrid
                metrics={[
                    {
                        label: t('notifications.all'),
                        value: props.counts.all,
                        detail: t('notifications.all_detail'),
                        icon: 'bi-envelope',
                        tone: 'ink',
                    },
                    {
                        label: t('notifications.unread'),
                        value: props.counts.unread,
                        detail: t('notifications.unread_detail'),
                        icon: 'bi-envelope',
                        tone: 'amber',
                    },
                    {
                        label: t('notifications.read'),
                        value: props.counts.read,
                        detail: t('notifications.read_detail'),
                        icon: 'bi-check2-circle',
                        tone: 'teal',
                    },
                ]}
            />

            <WorkspacePanel
                eyebrow={t('notifications.activity')}
                title={t('notifications.inbox')}
                description={t('notifications.inbox_description')}
            >
                <NotificationFilters
                    filters={props.filters}
                    counts={props.counts}
                    typeCounts={props.typeCounts}
                />
                <NotificationList items={props.notificationItems} />
            </WorkspacePanel>
        </AdminLayout>
    );
}
