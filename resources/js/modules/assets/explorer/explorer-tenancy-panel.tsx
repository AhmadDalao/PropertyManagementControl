import { Link } from '@inertiajs/react';

import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate, localizedNumber } from '@/lib/utils';

import { ExplorerFact } from './explorer-fact';
import type {
    PropertyExplorerLease,
    PropertyExplorerPayload,
    PropertyExplorerSelected,
} from './types';

export function ExplorerTenancyPanel({
    selected,
    lease,
    modules,
    canCreate,
}: {
    selected: PropertyExplorerSelected;
    lease: PropertyExplorerLease | null;
    modules: PropertyExplorerPayload['modules'];
    canCreate: boolean;
}) {
    const { locale, t } = useTranslator();

    if (!lease) {
        return (
            <article
                className="pmc-explorer-tenancy is-empty"
                data-explorer-focus-section="tenancy"
            >
                <i className="bi bi-house-check" aria-hidden="true" />
                <div>
                    <span>{t('assets.explorer.tenancy')}</span>
                    <strong>
                        {selected.rentable
                            ? t('assets.explorer.no_active_tenant')
                            : t('assets.explorer.not_rentable_node')}
                    </strong>
                    <p>
                        {selected.rentable
                            ? t('assets.explorer.no_active_tenant_help')
                            : t('assets.explorer.open_child_to_tenant')}
                    </p>
                </div>
                {selected.rentable && modules.leases && canCreate ? (
                    <Link href={selected.create_lease_href}>
                        {t('assets.explorer.create_lease')}
                    </Link>
                ) : null}
            </article>
        );
    }

    return (
        <article
            className="pmc-explorer-tenancy"
            data-explorer-focus-section="tenancy"
        >
            <header>
                <div>
                    <span>{t('assets.explorer.current_tenancy')}</span>
                    <strong>
                        {lease.tenant_name ??
                            t('assets.explorer.unknown_tenant')}
                    </strong>
                    <small>
                        {lease.code} · {humanDate(lease.ends_at, locale)}
                    </small>
                </div>
                <StatusBadge value={lease.status} />
            </header>
            <dl>
                <ExplorerFact
                    label={t('assets.explorer.paid')}
                    value={currency(lease.total_paid, locale, lease.currency)}
                />
                <ExplorerFact
                    label={t('assets.explorer.remaining')}
                    value={currency(
                        lease.balance_remaining,
                        locale,
                        lease.currency,
                    )}
                />
                <ExplorerFact
                    label={t('assets.explorer.arrears')}
                    value={currency(lease.arrears, locale, lease.currency)}
                />
                <ExplorerFact
                    label={t('assets.explorer.days_left')}
                    value={localizedNumber(lease.days_remaining ?? 0, locale)}
                />
            </dl>
            <footer>
                <Link href={lease.href}>{t('assets.explorer.open_lease')}</Link>
                {modules.tenants && lease.tenant_href ? (
                    <Link href={lease.tenant_href}>
                        {t('assets.explorer.open_tenant')}
                    </Link>
                ) : null}
                {modules.payments ? (
                    <Link href={lease.payments_href}>
                        {t('assets.explorer.payment_history')}
                    </Link>
                ) : null}
                {modules.maintenance ? (
                    <Link href={selected.maintenance_href}>
                        {t('assets.explorer.maintenance_history')}
                    </Link>
                ) : null}
            </footer>
        </article>
    );
}
