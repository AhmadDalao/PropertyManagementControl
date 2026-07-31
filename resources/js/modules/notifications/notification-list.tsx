import { router } from '@inertiajs/react';

import { TablePagination } from '@/components/data-table/table-pagination';
import { useTranslator } from '@/lib/i18n';

import type { NotificationItem, NotificationIndexPageProps } from './types';

export function NotificationList({
    items,
}: {
    items: NotificationIndexPageProps['notificationItems'];
}) {
    const { t, locale } = useTranslator();

    function openNotification(item: NotificationItem) {
        router.post(item.read_href);
    }

    return (
        <>
            {items.data.length > 0 ? (
                <div className="list-group list-group-flush rounded-4 overflow-hidden border">
                    {items.data.map((item) => (
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
                                <span className="d-flex align-items-center gap-2 flex-wrap">
                                    <strong>{item.title}</strong>
                                    <span className="badge rounded-pill text-bg-light border">
                                        {item.resource_label}
                                    </span>
                                </span>
                                <span className="text-muted">{item.body}</span>
                                {item.created_at ? (
                                    <small className="text-muted">
                                        {new Intl.DateTimeFormat(locale, {
                                            dateStyle: 'medium',
                                            timeStyle: 'short',
                                        }).format(new Date(item.created_at))}
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

            <TablePagination data={items} />
        </>
    );
}
