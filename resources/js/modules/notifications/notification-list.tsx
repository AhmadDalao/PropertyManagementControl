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
                <div className="pmc-notification-list">
                    {items.data.map((item) => (
                        <button
                            key={item.id}
                            type="button"
                            className={item.read ? '' : 'is-unread'}
                            onClick={() => openNotification(item)}
                        >
                            <span
                                className={`pmc-notification-icon is-${item.tone}`}
                            >
                                <i
                                    className={`bi ${item.icon}`}
                                    aria-hidden="true"
                                />
                            </span>
                            <span className="pmc-notification-copy">
                                <span>
                                    <strong>{item.title}</strong>
                                    <em>{item.resource_label}</em>
                                </span>
                                <span>{item.body}</span>
                                {item.created_at ? (
                                    <small>
                                        {new Intl.DateTimeFormat(locale, {
                                            dateStyle: 'medium',
                                            timeStyle: 'short',
                                        }).format(new Date(item.created_at))}
                                    </small>
                                ) : null}
                            </span>
                            {!item.read ? (
                                <span
                                    className="pmc-notification-new"
                                    aria-label={t('notifications.new')}
                                />
                            ) : null}
                        </button>
                    ))}
                </div>
            ) : (
                <div className="pmc-notification-empty">
                    <i className="bi bi-envelope" />
                    <h3>{t('notifications.no_notifications')}</h3>
                    <p>{t('notifications.no_notifications_help')}</p>
                </div>
            )}

            <TablePagination data={items} />
        </>
    );
}
