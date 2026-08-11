import { Link } from '@inertiajs/react';

import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { TenantDashboardProps } from '../types';

export function TenantHeader({ props }: { props: TenantDashboardProps }) {
    const { t } = useTranslator();
    const lease = props.tenantPortal.lease;
    const isArabic = props.app.locale === 'ar';

    return (
        <section className="pmc-tenant-home-hero">
            <div>
                <span>{t('tenant_portal.portal_eyebrow')}</span>
                <h1>
                    {t('dashboard.welcome_user', undefined, {
                        name: props.auth.user?.name ?? '',
                    })}
                </h1>
                <p>
                    {lease
                        ? `${lease.code} · ${isArabic ? lease.leaseable?.title_ar || lease.leaseable?.title_en : lease.leaseable?.title_en || lease.leaseable?.title_ar}`
                        : t('tenant_portal.empty_lease_description')}
                </p>
                <div>
                    {lease ? (
                        <StatusBadge
                            value="active"
                            label={t('dashboard.active_lease')}
                        />
                    ) : null}
                    <Link href="/maintenance-requests/create">
                        <i className="bi bi-plus-lg" />
                        {t('tenant_portal.new_request')}
                    </Link>
                </div>
            </div>
            <div className="pmc-tenant-home-building" aria-hidden="true">
                <i className="bi bi-buildings" />
                <span>
                    {isArabic
                        ? lease?.leaseable?.title_ar ||
                          lease?.leaseable?.title_en
                        : lease?.leaseable?.title_en ||
                          lease?.leaseable?.title_ar}
                </span>
            </div>
        </section>
    );
}
