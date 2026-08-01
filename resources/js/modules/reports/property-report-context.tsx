import { Link } from '@inertiajs/react';

import { StatusBadge, humanLabel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency, localizedNumber, percent } from '@/lib/utils';

import type { PropertyReportPageProps } from './types';

export function PropertyReportContext({
    props,
}: {
    props: PropertyReportPageProps;
}) {
    const { locale, t, text } = useTranslator();
    const property = props.property;
    const title = localized(property.title_en, property.title_ar, locale);
    const portfolio = localized(
        property.portfolio.name_en,
        property.portfolio.name_ar,
        locale,
    );
    const address = localized(property.address_en, property.address_ar, locale);
    const structure = [
        ['records', property.structure.records, 'bi-diagram-3'],
        ['floors', property.structure.floors, 'bi-layers'],
        ['units_spaces', property.structure.units, 'bi-door-open'],
        ['rentable_spaces', property.structure.rentable, 'bi-house-check'],
        ['occupied_spaces', property.structure.occupied, 'bi-building-check'],
        ['vacant_spaces', property.structure.vacant, 'bi-building'],
        ['active_tenants', property.structure.active_tenants, 'bi-people'],
    ] as const;
    const sources = [
        ['payments', property.links.payments, 'bi-cash-stack'],
        ['leases', property.links.leases, 'bi-file-earmark-text'],
        ['maintenance', property.links.maintenance, 'bi-tools'],
        ['expenses', property.links.expenses, 'bi-receipt'],
        ['action_center', property.links.action_center, 'bi-list-check'],
    ] as const;

    return (
        <section className="pmc-property-report-context">
            <header>
                <div>
                    <span className="pmc-property-report-code">
                        {property.code}
                    </span>
                    <div>
                        <h2>{title}</h2>
                        <p>
                            {portfolio || t('reports.all_portfolios')}
                            {property.portfolio.code
                                ? ` · ${property.portfolio.code}`
                                : ''}
                        </p>
                    </div>
                </div>
                <div className="pmc-property-report-badges">
                    {property.is_showcase ? (
                        <em>{t('portfolios.showcase')}</em>
                    ) : null}
                    <StatusBadge value={property.status} />
                    <span>{text(humanLabel(property.usage_type))}</span>
                </div>
            </header>

            <div className="pmc-property-report-facts">
                <Fact
                    label={t('reports.valuation')}
                    value={compactCurrency(
                        property.valuation_amount,
                        locale,
                        property.currency,
                    )}
                />
                <Fact
                    label={t('reports.occupancy')}
                    value={percent(props.summary.occupancyRate, locale)}
                />
                <Fact
                    label={t('reports.owner')}
                    value={property.owner?.name ?? t('reports.unassigned')}
                />
                <Fact
                    label={t('reports.manager')}
                    value={property.manager?.name ?? t('reports.unassigned')}
                />
                <Fact
                    label={t('reports.address')}
                    value={address || t('reports.not_recorded')}
                />
            </div>

            <div className="pmc-property-report-structure">
                {structure.map(([key, value, icon]) => (
                    <article key={key}>
                        <i className={`bi ${icon}`} aria-hidden="true" />
                        <span>{t(`reports.${key}`)}</span>
                        <strong>{localizedNumber(value, locale)}</strong>
                    </article>
                ))}
            </div>

            <footer>
                <div>
                    <strong>{t('reports.operational_sources')}</strong>
                    <span>{t('reports.operational_sources_help')}</span>
                </div>
                <nav aria-label={t('reports.operational_sources')}>
                    {sources.map(([key, href, icon]) => (
                        <Link href={href} key={key}>
                            <i className={`bi ${icon}`} aria-hidden="true" />
                            {t(`reports.open_${key}`)}
                        </Link>
                    ))}
                </nav>
            </footer>
        </section>
    );
}

function Fact({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <span>{label}</span>
            <strong>{value}</strong>
        </div>
    );
}

function localized(
    english: string | null | undefined,
    arabic: string | null | undefined,
    locale: string,
) {
    return locale === 'ar' ? arabic || english || '' : english || arabic || '';
}
