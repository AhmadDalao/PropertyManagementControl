import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { TenantLease } from './types';

export function LeaseContractPanel({ lease }: { lease: TenantLease }) {
    const { locale, t } = useTranslator();
    const address =
        locale === 'ar'
            ? lease.asset?.address_ar || lease.asset?.address || '-'
            : lease.asset?.address || lease.asset?.address_ar || '-';

    return (
        <section className="pmc-portal-panel">
            <header>
                <div>
                    <span>{t('tenant_portal.contract')}</span>
                    <h2>{t('tenant_portal.lease_information')}</h2>
                </div>
                <strong>{lease.code}</strong>
            </header>
            <dl className="pmc-portal-detail-grid">
                <Detail
                    label={t('tenant_portal.start_date')}
                    value={humanDate(lease.started_at, locale)}
                />
                <Detail
                    label={t('tenant_portal.end_date')}
                    value={humanDate(lease.ends_at, locale)}
                />
                <Detail
                    label={t('tenant_portal.monthly_rent')}
                    value={currency(lease.rent_amount, locale, lease.currency)}
                />
                <Detail
                    label={t('tenant_portal.deposit')}
                    value={currency(
                        lease.deposit_amount,
                        locale,
                        lease.currency,
                    )}
                />
                <Detail
                    label={t('tenant_portal.frequency')}
                    value={t(`leases.frequency_${lease.payment_frequency}`)}
                />
                <Detail
                    label={t('tenant_portal.signed_date')}
                    value={humanDate(lease.signed_at, locale)}
                />
                <Detail label={t('tenant_portal.address')} value={address} />
                <Detail
                    label={t('tenant_portal.usage')}
                    value={t(
                        `assets.${lease.asset?.usage_type ?? 'residential'}`,
                    )}
                />
            </dl>
        </section>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt>{label}</dt>
            <dd>{value}</dd>
        </div>
    );
}
