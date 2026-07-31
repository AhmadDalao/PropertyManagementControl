import { Head, Link, router, usePage } from '@inertiajs/react';

import { TablePagination } from '@/components/data-table/table-pagination';
import {
    MetricGrid,
    WorkspaceHeader,
    WorkspacePanel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import type { NotificationIndexPageProps, NotificationItem } from './types';

export default function NotificationsIndexPage() {
    const { props } = usePage<NotificationIndexPageProps>();
    const { t, locale } = useTranslator();
    const filters = [
        ['all', t('notifications.all'), props.counts.all],
        ['unread', t('notifications.unread'), props.counts.unread],
        ['read', t('notifications.read'), props.counts.read],
    ] as const;

    function openNotification(item: NotificationItem) {
        router.post(item.read_href);
    }

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
                <div className="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
                    <div
                        className="pmc-filter-chips"
                        aria-label={t('notifications.filter')}
                    >
                        {filters.map(([status, label, count]) => (
                            <Link
                                key={status}
                                href={`/notifications?status=${status}`}
                                preserveScroll
                                preserveState
                                className={
                                    props.filters.status === status
                                        ? 'active'
                                        : ''
                                }
                            >
                                {label} <strong>{count}</strong>
                            </Link>
                        ))}
                    </div>

                    {props.counts.unread > 0 ? (
                        <button
                            type="button"
                            className="btn btn-outline-dark"
                            onClick={() =>
                                router.post('/notifications/read-all')
                            }
                        >
                            <i className="bi bi-check2 me-2" />
                            {t('notifications.mark_all_read')}
                        </button>
                    ) : null}
                </div>

                {props.notificationItems.data.length > 0 ? (
                    <div className="list-group list-group-flush rounded-4 overflow-hidden border">
                        {props.notificationItems.data.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                className={`list-group-item list-group-item-action d-flex align-items-start gap-3 p-3 p-md-4 ${item.read ? '' : 'bg-warning-subtle'}`}
                                onClick={() => openNotification(item)}
                            >
                                <span
                                    className={`badge rounded-circle d-grid p-3 text-bg-${item.tone === 'blue' ? 'primary' : item.tone === 'neutral' ? 'secondary' : item.tone}`}
                                >
                                    <i
                                        className={`bi ${item.icon}`}
                                        aria-hidden="true"
                                    />
                                </span>
                                <span className="d-grid gap-1 flex-grow-1 text-start">
                                    <strong>{item.title}</strong>
                                    <span className="text-muted">
                                        {item.body}
                                    </span>
                                    {item.created_at ? (
                                        <small className="text-muted">
                                            {new Intl.DateTimeFormat(locale, {
                                                dateStyle: 'medium',
                                                timeStyle: 'short',
                                            }).format(
                                                new Date(item.created_at),
                                            )}
                                        </small>
                                    ) : null}
                                </span>
                                {!item.read ? (
                                    <span className="badge text-bg-warning">
                                        {t('notifications.new')}
                                    </span>
                                ) : null}
                            </button>
                        ))}
                    </div>
                ) : (
                    <div className="py-5 px-3 text-center">
                        <i className="bi bi-envelope fs-1 text-muted" />
                        <h3 className="h5 mt-3">
                            {t('notifications.no_notifications')}
                        </h3>
                        <p className="text-muted mb-0">
                            {t('notifications.no_notifications_help')}
                        </p>
                    </div>
                )}

                <TablePagination data={props.notificationItems} />
            </WorkspacePanel>
        </AdminLayout>
    );
}
