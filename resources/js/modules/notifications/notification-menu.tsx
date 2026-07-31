import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { NotificationItem, NotificationSummary } from './types';

export function NotificationMenu({
    notifications,
}: {
    notifications: NotificationSummary;
}) {
    const [open, setOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);
    const triggerRef = useRef<HTMLButtonElement>(null);
    const { t, locale } = useTranslator();

    useEffect(() => {
        if (!open) {
            return;
        }

        const closeOutside = (event: PointerEvent) => {
            if (!menuRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
                triggerRef.current?.focus();
            }
        };

        document.addEventListener('pointerdown', closeOutside);
        document.addEventListener('keydown', closeOnEscape);

        return () => {
            document.removeEventListener('pointerdown', closeOutside);
            document.removeEventListener('keydown', closeOnEscape);
        };
    }, [open]);

    function openNotification(item: NotificationItem) {
        setOpen(false);
        router.post(item.read_href);
    }

    return (
        <div ref={menuRef} className="pmc-account-menu">
            <button
                ref={triggerRef}
                type="button"
                className="btn btn-outline-secondary rounded-circle d-inline-flex align-items-center justify-content-center position-relative p-0"
                style={{ width: '2.75rem', height: '2.75rem' }}
                aria-label={t('notifications.open_menu')}
                aria-haspopup="menu"
                aria-expanded={open}
                onClick={() => setOpen((current) => !current)}
            >
                <i className="bi bi-envelope" aria-hidden="true" />
                {notifications.unread_count > 0 ? (
                    <span className="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {notifications.unread_count > 99
                            ? '99+'
                            : notifications.unread_count}
                    </span>
                ) : null}
            </button>

            {open ? (
                <div
                    className="pmc-account-panel"
                    role="menu"
                    style={{
                        position: 'fixed',
                        top: 'calc(var(--pmc-topbar-height) + 0.5rem)',
                        insetInlineEnd: 'clamp(0.75rem, 2vw, 1.5rem)',
                        width: 'calc(100% - 1.5rem)',
                        maxWidth: '24rem',
                        maxHeight: 'min(36rem, calc(100vh - 5rem))',
                        overflowY: 'auto',
                    }}
                >
                    <div className="pmc-account-panel-head d-flex align-items-center justify-content-between gap-3 flex-row">
                        <div className="d-grid overflow-hidden">
                            <strong>{t('notifications.recent')}</strong>
                            <small>
                                {t('notifications.unread_count', undefined, {
                                    count: notifications.unread_count,
                                })}
                            </small>
                        </div>
                        {notifications.unread_count > 0 ? (
                            <button
                                type="button"
                                className="p-0 text-nowrap"
                                onClick={() => {
                                    setOpen(false);
                                    router.post('/notifications/read-all');
                                }}
                            >
                                {t('notifications.mark_all_read')}
                            </button>
                        ) : null}
                    </div>

                    {notifications.recent.length > 0 ? (
                        notifications.recent.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                role="menuitem"
                                className={`d-flex gap-3 ${item.read ? '' : 'bg-warning-subtle'}`}
                                onClick={() => openNotification(item)}
                            >
                                <i
                                    className={`bi ${item.icon} flex-shrink-0`}
                                    aria-hidden="true"
                                />
                                <span className="d-grid gap-1 overflow-hidden">
                                    <strong className="text-wrap">
                                        {item.title}
                                    </strong>
                                    <small className="text-muted text-wrap">
                                        {item.body}
                                    </small>
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
                            </button>
                        ))
                    ) : (
                        <div className="px-3 py-4 text-muted small text-center">
                            {t('notifications.no_notifications')}
                        </div>
                    )}

                    <Link
                        href="/notifications"
                        role="menuitem"
                        className="mt-1 text-center"
                        onClick={() => setOpen(false)}
                    >
                        {t('notifications.view_all')}
                    </Link>
                </div>
            ) : null}
        </div>
    );
}
