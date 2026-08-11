import { Link, router } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { NotificationIndexPageProps } from './types';

export function NotificationCenter({
    props,
}: {
    props: NotificationIndexPageProps;
}) {
    const { t } = useTranslator();
    const types = [
        ['maintenance_request', 'bi-tools'],
        ['payment', 'bi-cash-stack'],
        ['lease', 'bi-file-earmark-text'],
        ['document', 'bi-file-earmark-pdf'],
    ] as const;

    return (
        <aside className="pmc-notification-center">
            <span>{t('notifications.activity')}</span>
            <h2>{t('notifications.center_title')}</h2>
            <p>{t('notifications.center_description')}</p>
            <div className="pmc-notification-center-types">
                {types.map(([type, icon]) => (
                    <Link href={`/notifications?type=${type}`} key={type}>
                        <i className={`bi ${icon}`} />
                        <span>{t(`notifications.types.${type}`)}</span>
                        <strong>{props.typeCounts[type]}</strong>
                    </Link>
                ))}
            </div>
            {props.counts.unread > 0 ? (
                <button
                    type="button"
                    onClick={() => router.post('/notifications/read-all')}
                >
                    <i className="bi bi-check2-all" />
                    {t('notifications.mark_all_read')}
                </button>
            ) : null}
        </aside>
    );
}
