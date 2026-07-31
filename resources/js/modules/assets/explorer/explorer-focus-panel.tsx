import { Link } from '@inertiajs/react';

import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate, localizedNumber } from '@/lib/utils';

import type {
    PropertyExplorerLease,
    PropertyExplorerPayload,
    PropertyExplorerSelected,
} from './types';

export function ExplorerFocusPanel({
    explorer,
    canCreate,
}: {
    explorer: PropertyExplorerPayload;
    canCreate: boolean;
}) {
    const { locale, t } = useTranslator();
    const selected = explorer.selected;

    if (!selected) {
        return null;
    }

    return (
        <section className="pmc-explorer-focus">
            <header>
                <div>
                    <span>{selected.code}</span>
                    <h2>{localizedTitle(selected, locale)}</h2>
                    <div className="pmc-explorer-focus-badges">
                        <StatusBadge value={selected.occupancy_status} />
                        <em>{t(`assets.types.${selected.asset_type}`)}</em>
                    </div>
                </div>
                <div className="pmc-explorer-focus-actions">
                    <Link href={selected.detail_href} className="btn btn-light">
                        {t('assets.explorer.full_details')}
                    </Link>
                    {canCreate ? (
                        <>
                            <Link
                                href={selected.edit_href}
                                className="btn btn-light"
                            >
                                <i
                                    className="bi bi-pencil"
                                    aria-hidden="true"
                                />
                                {t('actions.edit')}
                            </Link>
                            <Link
                                href={selected.add_child_href}
                                className="btn btn-primary"
                            >
                                <i
                                    className="bi bi-plus-lg"
                                    aria-hidden="true"
                                />
                                {t('assets.explorer.add_inside')}
                            </Link>
                        </>
                    ) : null}
                </div>
            </header>

            <div className="pmc-explorer-focus-body">
                <NodeFacts selected={selected} />
                <TenancyPanel
                    selected={selected}
                    lease={explorer.active_lease}
                    modules={explorer.modules}
                    canCreate={canCreate}
                />
            </div>
        </section>
    );
}

function NodeFacts({ selected }: { selected: PropertyExplorerSelected }) {
    const { locale, t } = useTranslator();

    return (
        <article className="pmc-explorer-facts">
            <span>{t('assets.explorer.node_overview')}</span>
            <dl>
                <Fact
                    label={t('assets.explorer.child_records')}
                    value={localizedNumber(selected.children_count, locale)}
                />
                <Fact
                    label={t('assets.value')}
                    value={currency(
                        selected.valuation_amount,
                        locale,
                        selected.currency,
                    )}
                />
                <Fact
                    label={t('assets.area')}
                    value={
                        selected.area
                            ? `${localizedNumber(selected.area, locale)} m²`
                            : '-'
                    }
                />
                <Fact
                    label={t('assets.explorer.manager')}
                    value={selected.manager?.name ?? '-'}
                />
                <Fact
                    label={t('assets.explorer.owner')}
                    value={selected.owner?.name ?? '-'}
                />
                <Fact
                    label={t('assets.address')}
                    value={selected.address ?? '-'}
                />
            </dl>
        </article>
    );
}

function TenancyPanel({
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
            <article className="pmc-explorer-tenancy is-empty">
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
        <article className="pmc-explorer-tenancy">
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
                <Fact
                    label={t('assets.explorer.paid')}
                    value={currency(lease.total_paid, locale, lease.currency)}
                />
                <Fact
                    label={t('assets.explorer.remaining')}
                    value={currency(
                        lease.balance_remaining,
                        locale,
                        lease.currency,
                    )}
                />
                <Fact
                    label={t('assets.explorer.arrears')}
                    value={currency(lease.arrears, locale, lease.currency)}
                />
                <Fact
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

function Fact({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt>{label}</dt>
            <dd>{value}</dd>
        </div>
    );
}

function localizedTitle(
    record: { title_en: string; title_ar?: string | null },
    locale: string,
) {
    return locale === 'ar'
        ? record.title_ar || record.title_en
        : record.title_en || record.title_ar || '';
}
