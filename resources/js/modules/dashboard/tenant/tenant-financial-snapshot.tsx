import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { compactCurrency, currency, humanDate } from '@/lib/utils';

import type { TenantDashboardProps } from '../types';

export function TenantFinancialSnapshot({
    props,
}: {
    props: TenantDashboardProps;
}) {
    const { locale, t } = useTranslator();
    const lease = props.tenantPortal.lease;

    if (!lease) {
        return null;
    }

    const note =
        props.stats.overdue > 0
            ? t('dashboard.overdue_amount', undefined, {
                  amount: currency(props.stats.overdue, locale, lease.currency),
              })
            : lease.next_due_date
              ? t('dashboard.next_due_date', undefined, {
                    date: humanDate(lease.next_due_date, locale),
                })
              : t('dashboard.no_amount_due');

    return (
        <section className="pmc-tenant-snapshot pmc-tenant-financial-snapshot">
            <header>
                <div>
                    <span>{t('tenant_portal.financial')}</span>
                    <h2>{t('tenant_portal.payments_and_receipts')}</h2>
                </div>
                <Link href="/my-payments">
                    {t('tenant_portal.my_payments')}
                </Link>
            </header>
            <dl>
                <div>
                    <dt>{t('tenant_portal.total_paid')}</dt>
                    <dd>
                        {compactCurrency(
                            props.stats.paidAmount,
                            locale,
                            lease.currency,
                        )}
                    </dd>
                </div>
                <div>
                    <dt>{t('dashboard.due_now')}</dt>
                    <dd>
                        {compactCurrency(
                            props.stats.dueNow,
                            locale,
                            lease.currency,
                        )}
                    </dd>
                </div>
                <div>
                    <dt>{t('tenant_portal.outstanding')}</dt>
                    <dd>
                        {compactCurrency(
                            props.stats.amountLeft,
                            locale,
                            lease.currency,
                        )}
                    </dd>
                </div>
            </dl>
            <p className={props.stats.overdue > 0 ? 'is-overdue' : ''}>
                <i
                    className={`bi ${props.stats.overdue > 0 ? 'bi-exclamation-circle' : 'bi-calendar-check'}`}
                    aria-hidden="true"
                />
                {note}
            </p>
        </section>
    );
}
