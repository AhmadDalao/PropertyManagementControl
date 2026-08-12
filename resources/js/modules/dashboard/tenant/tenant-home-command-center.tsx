import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import {
    compactCurrency,
    currency,
    humanDate,
    localizedNumber,
} from '@/lib/utils';

import type { TenantDashboardProps } from '../types';

export function TenantHomeCommandCenter({
    props,
}: {
    props: TenantDashboardProps;
}) {
    const { locale, t } = useTranslator();
    const lease = props.tenantPortal.lease;
    const assetTitle =
        locale === 'ar'
            ? lease?.leaseable?.title_ar || lease?.leaseable?.title_en
            : lease?.leaseable?.title_en || lease?.leaseable?.title_ar;
    const currencyCode = lease?.currency ?? 'SAR';
    const activity = [
        ...props.tenantPortal.payments.map((payment) => ({
            key: `payment-${payment.id}`,
            href: payment.receipt_url,
            icon: 'bi-wallet2',
            tone: 'success',
            title: t('dashboard.payment_received'),
            detail: currency(payment.amount, locale, payment.currency),
            date: payment.received_on,
            native: true,
        })),
        ...props.tenantPortal.requests.map((request) => ({
            key: `request-${request.id}`,
            href: `/maintenance-requests/${request.id}`,
            icon: 'bi-tools',
            tone: 'info',
            title: request.title,
            detail: t(`status.${request.status}`, request.status),
            date: request.created_at,
            native: false,
        })),
    ]
        .sort((left, right) => timestamp(right.date) - timestamp(left.date))
        .slice(0, 5);

    return (
        <div className="pmc-tenant-home-dashboard">
            <section className="pmc-tenant-welcome-card">
                <div>
                    <span>{t('tenant_portal.portal_eyebrow')}</span>
                    <h1>
                        {t('dashboard.welcome_user', undefined, {
                            name: props.auth.user?.name ?? '',
                        })}
                    </h1>
                    <strong>{t('tenant_portal.portal_eyebrow')}</strong>
                </div>
                <div>
                    <i className="bi bi-buildings" aria-hidden="true" />
                    <span>
                        {assetTitle ?? t('tenant_portal.current_rental')}
                    </span>
                </div>
            </section>

            <section className="pmc-tenant-home-metrics">
                <TenantMetric
                    label={t('dashboard.active_lease')}
                    value={localizedNumber(lease ? 1 : 0, locale)}
                    href="/my-lease"
                />
                <TenantMetric
                    label={t('tenant_portal.outstanding')}
                    value={compactCurrency(
                        props.stats.amountLeft,
                        locale,
                        currencyCode,
                    )}
                    href="/my-payments"
                    tone={props.stats.amountLeft > 0 ? 'warning' : 'success'}
                />
                <TenantMetric
                    label={t('dashboard.open_requests')}
                    value={localizedNumber(
                        props.stats.maintenanceRequests,
                        locale,
                    )}
                    href="/maintenance-requests"
                />
            </section>

            <section className="pmc-tenant-recent-activity">
                <header>
                    <h2>{t('dashboard.recent_activity')}</h2>
                    <Link href="/notifications">{t('actions.view_all')}</Link>
                </header>
                <div>
                    {activity.length > 0 ? (
                        activity.map((item) => {
                            const content = (
                                <>
                                    <i
                                        className={`bi ${item.icon} is-${item.tone}`}
                                        aria-hidden="true"
                                    />
                                    <span>
                                        <strong>{item.title}</strong>
                                        <small>{item.detail}</small>
                                    </span>
                                    <time>
                                        {humanDate(item.date, props.app.locale)}
                                    </time>
                                </>
                            );

                            return item.native ? (
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
                <Link
                    className="pmc-tenant-new-request"
                    href="/maintenance-requests/create"
                >
                    <i className="bi bi-plus-lg" />
                    {t('tenant_portal.new_request')}
                </Link>
            </section>
        </div>
    );
}

function timestamp(value: string | null): number {
    return Date.parse(value ?? '');
}

function TenantMetric({
    label,
    value,
    href,
    tone = 'default',
}: {
    label: string;
    value: string;
    href: string;
    tone?: 'default' | 'success' | 'warning';
}) {
    return (
        <Link href={href} className={`is-${tone}`}>
            <span>{label}</span>
            <strong>{value}</strong>
        </Link>
    );
}
