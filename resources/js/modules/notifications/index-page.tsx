import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/notifications.css';

import { WorkspaceHeader, WorkspacePanel } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { NotificationCenter } from './notification-center';
import { NotificationFilters } from './notification-filters';
import { NotificationList } from './notification-list';
import { NotificationMetrics } from './notification-metrics';
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

            <NotificationMetrics
                counts={props.counts}
                typeCounts={props.typeCounts}
            />

            <div className="pmc-notification-layout">
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
                <NotificationCenter props={props} />
            </div>
        </AdminLayout>
    );
}
