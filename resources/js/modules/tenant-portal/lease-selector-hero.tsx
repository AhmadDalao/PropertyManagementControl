import { router } from '@inertiajs/react';

import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { TenantLeasePageProps } from './types';

export function LeaseSelectorHero({ props }: { props: TenantLeasePageProps }) {
    const { locale, t } = useTranslator();
    const lease = props.lease;

    if (!lease) {
        return null;
    }

    return (
        <>
            {props.leases.length > 1 ? (
                <label className="pmc-portal-lease-picker">
                    <span>{t('tenant_portal.choose_lease')}</span>
                    <select
                        value={lease.id}
                        onChange={(event) =>
                            router.get('/my-lease', {
                                lease_id: event.currentTarget.value,
                            })
                        }
                    >
                        {props.leases.map((item) => (
                            <option value={item.id} key={item.id}>
                                {item.code} ·{' '}
                                {locale === 'ar'
                                    ? item.asset_title_ar || item.asset_title_en
                                    : item.asset_title_en ||
                                      item.asset_title_ar}
                            </option>
                        ))}
                    </select>
                </label>
            ) : null}
            <section className="pmc-portal-lease-hero">
                <div className="pmc-portal-property-mark">
                    <i className="bi bi-buildings" aria-hidden="true" />
                </div>
                <div>
                    <span>{t('tenant_portal.current_rental')}</span>
                    <h2>
                        {locale === 'ar'
                            ? lease.asset?.title_ar || lease.asset?.title_en
                            : lease.asset?.title_en || lease.asset?.title_ar}
                    </h2>
                    <p>{lease.asset?.code || lease.code}</p>
                </div>
                <StatusBadge value={lease.status} />
            </section>
        </>
    );
}
