import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { currency, humanDate, localizedNumber } from '@/lib/utils';

import type { TenantDashboardProps } from '../types';

type Lease = NonNullable<TenantDashboardProps['tenantPortal']['lease']>;

export function TenantContractSnapshot({ lease }: { lease: Lease }) {
    const { locale, t } = useTranslator();

    return (
        <section className="pmc-tenant-snapshot pmc-tenant-contract-snapshot">
            <header>
                <div>
                    <span>{t('tenant_portal.contract')}</span>
                    <h2>{lease.code}</h2>
                </div>
                <Link href="/my-lease">{t('tenant_portal.my_lease')}</Link>
            </header>
            <dl>
                <div>
                    <dt>{t('tenant_portal.days_left')}</dt>
                    <dd>
                        {lease.days_remaining === null
                            ? t('dashboard.not_available')
                            : localizedNumber(lease.days_remaining, locale)}
                    </dd>
                </div>
                <div>
                    <dt>{t('tenant_portal.end_date')}</dt>
                    <dd>{humanDate(lease.ends_at, locale)}</dd>
                </div>
                <div>
                    <dt>{t('tenant_portal.monthly_rent')}</dt>
                    <dd>
                        {currency(lease.rent_amount, locale, lease.currency)}
                    </dd>
                </div>
            </dl>
        </section>
    );
}
