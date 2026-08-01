import { Link } from '@inertiajs/react';

import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import { StatementPanel } from './statement-panel';
import type {
    TenantStatementFinancial,
    TenantStatementLease,
} from './statement-types';

export function TenantFinancialCards({
    financials,
}: {
    financials: TenantStatementFinancial[];
}) {
    const { locale, t } = useTranslator();

    return (
        <div className="pmc-tenant-financial-grid">
            {financials.map((financial) => (
                <article
                    className="pmc-tenant-financial-card"
                    key={financial.currency}
                >
                    <header>
                        <span>{t('tenants.financial_position')}</span>
                        <strong>{financial.currency}</strong>
                    </header>
                    <dl>
                        <div>
                            <dt>{t('tenants.period_scheduled')}</dt>
                            <dd>
                                {currency(
                                    financial.scheduled_due,
                                    locale,
                                    financial.currency,
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt>{t('tenants.period_paid')}</dt>
                            <dd>
                                {currency(
                                    financial.scheduled_paid,
                                    locale,
                                    financial.currency,
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt>{t('tenants.period_received')}</dt>
                            <dd>
                                {currency(
                                    financial.received,
                                    locale,
                                    financial.currency,
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt>{t('tenants.contract_balance')}</dt>
                            <dd>
                                {currency(
                                    financial.contract_balance,
                                    locale,
                                    financial.currency,
                                )}
                            </dd>
                        </div>
                    </dl>
                    <footer
                        className={
                            financial.overdue > 0 ? 'is-overdue' : undefined
                        }
                    >
                        <span>{t('tenants.overdue')}</span>
                        <strong>
                            {currency(
                                financial.overdue,
                                locale,
                                financial.currency,
                            )}
                        </strong>
                    </footer>
                </article>
            ))}
        </div>
    );
}

export function TenantLeaseCards({
    leases,
}: {
    leases: TenantStatementLease[];
}) {
    const { locale, t } = useTranslator();

    return (
        <StatementPanel
            title={t('tenants.contract_history')}
            description={t('tenants.contract_history_help')}
            count={leases.length}
            empty={t('tenants.no_leases')}
        >
            {leases.map((lease) => (
                <Link
                    className="pmc-tenant-statement-record"
                    href={lease.href}
                    key={lease.id}
                >
                    <div className="pmc-tenant-statement-record__head">
                        <div>
                            <strong>{lease.code}</strong>
                            <span>
                                {(locale === 'ar'
                                    ? lease.asset_ar || lease.asset_en
                                    : lease.asset_en || lease.asset_ar) ||
                                    t('leases.no_asset')}
                            </span>
                        </div>
                        <StatusBadge value={lease.status} />
                    </div>
                    <div className="pmc-tenant-statement-record__meta">
                        <span>
                            {humanDate(lease.started_at, locale)} -{' '}
                            {humanDate(lease.ends_at, locale)}
                        </span>
                        <strong>
                            {currency(lease.balance, locale, lease.currency)}
                        </strong>
                    </div>
                    {lease.overdue > 0 ? (
                        <div className="pmc-tenant-statement-record__alert">
                            {t('tenants.overdue_value', undefined, {
                                amount: currency(
                                    lease.overdue,
                                    locale,
                                    lease.currency,
                                ),
                            })}
                        </div>
                    ) : null}
                </Link>
            ))}
        </StatementPanel>
    );
}
