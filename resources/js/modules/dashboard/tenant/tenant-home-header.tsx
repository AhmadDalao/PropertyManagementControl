import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { TenantDashboardProps } from '../types';

export function TenantHomeHeader({ props }: { props: TenantDashboardProps }) {
    const { locale, t } = useTranslator();
    const lease = props.tenantPortal.lease;
    const assetTitle = lease
        ? locale === 'ar'
            ? lease.leaseable?.title_ar || lease.leaseable?.title_en
            : lease.leaseable?.title_en || lease.leaseable?.title_ar
        : null;

    return (
        <section className="pmc-tenant-command-header">
            <div className="pmc-tenant-command-intro">
                <span>{t('tenant_portal.portal_eyebrow')}</span>
                <h1>
                    {t('dashboard.welcome_user', undefined, {
                        name: props.auth.user?.name ?? '',
                    })}
                </h1>
                <p>{t('tenant_portal.home_description')}</p>
                {lease ? (
                    <nav aria-label={t('tenant_portal.lease_information')}>
                        <Link href="/my-lease">
                            <i
                                className="bi bi-file-earmark-text"
                                aria-hidden="true"
                            />
                            {t('tenant_portal.my_lease')}
                        </Link>
                        <a href={lease.contract_url}>
                            <i className="bi bi-download" aria-hidden="true" />
                            {t('tenant_portal.download_contract')}
                        </a>
                    </nav>
                ) : null}
            </div>

            <div className="pmc-tenant-current-rental">
                <i className="bi bi-buildings" aria-hidden="true" />
                <div>
                    <span>{t('tenant_portal.current_rental')}</span>
                    <strong>
                        {assetTitle ?? t('tenant_portal.empty_lease_title')}
                    </strong>
                    {lease ? (
                        <small>
                            {lease.code}
                            <em>{t(`status.${lease.status}`, lease.status)}</em>
                        </small>
                    ) : null}
                </div>
            </div>
        </section>
    );
}
