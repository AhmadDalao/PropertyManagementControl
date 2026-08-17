import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { TenantDashboardProps } from '../types';

type TenantActivity = {
    key: string;
    href: string;
    kind: 'payment' | 'request';
    icon: string;
    tone: string;
    title: string;
    detail: string;
    date: string | null;
};

export function TenantRecentActivity({
    props,
}: {
    props: TenantDashboardProps;
}) {
    const { t } = useTranslator();
    const activity: TenantActivity[] = [
        ...props.tenantPortal.payments.map((payment) => ({
            key: `payment-${payment.id}`,
            href: payment.receipt_url,
            kind: 'payment' as const,
            icon: 'bi-wallet2',
            tone: 'success',
            title: t('dashboard.payment_received'),
            detail: currency(
                payment.amount,
                props.app.locale,
                payment.currency,
            ),
            date: payment.received_on,
        })),
        ...props.tenantPortal.requests.map((request) => ({
            key: `request-${request.id}`,
            href: `/maintenance-requests/${request.id}`,
            kind: 'request' as const,
            icon: 'bi-tools',
            tone: 'info',
            title: request.title,
            detail: t(`status.${request.status}`, request.status),
            date: request.created_at,
        })),
    ]
        .sort((left, right) => timestamp(right.date) - timestamp(left.date))
        .slice(0, 4);

    return (
        <section className="pmc-tenant-recent-activity">
            <header>
                <h2>{t('dashboard.recent_activity')}</h2>
                <Link href="/notifications">{t('actions.view_all')}</Link>
            </header>
            <div>
                {activity.length > 0 ? (
                    activity.map((item) => {
                        const content = (
                            <ActivityContent
                                item={item}
                                locale={props.app.locale}
                            />
                        );

                        return item.kind === 'payment' ? (
                            <a key={item.key} href={item.href}>
                                {content}
                            </a>
                        ) : (
                            <Link key={item.key} href={item.href}>
                                {content}
                            </Link>
                        );
                    })
                ) : (
                    <p>{t('dashboard.no_recent_activity')}</p>
                )}
            </div>
        </section>
    );
}

function ActivityContent({
    item,
    locale,
}: {
    item: TenantActivity;
    locale: string;
}) {
    return (
        <>
            <i
                className={`bi ${item.icon} is-${item.tone}`}
                aria-hidden="true"
            />
            <span>
                <strong>{item.title}</strong>
                <small>{item.detail}</small>
            </span>
            <time>{humanDate(item.date, locale)}</time>
        </>
    );
}

function timestamp(value: string | null): number {
    return Date.parse(value ?? '');
}
