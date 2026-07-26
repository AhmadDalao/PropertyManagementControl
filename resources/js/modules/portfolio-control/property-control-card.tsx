import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import { compactCurrency, localizedNumber, percent } from '@/lib/utils';

import type { PortfolioControlProperty } from './types';

export function PropertyControlCard({
    property,
}: {
    property: PortfolioControlProperty;
}) {
    const { locale, t } = useTranslator();
    const title =
        locale === 'ar'
            ? property.title_ar || property.title_en
            : property.title_en || property.title_ar;
    const portfolio =
        locale === 'ar'
            ? property.portfolio_name_ar || property.portfolio_name_en
            : property.portfolio_name_en || property.portfolio_name_ar;
    const reasons = attentionReasons(property);

    return (
        <article
            className={`pmc-portfolio-control-card is-${property.attention}`}
        >
            <header>
                <div>
                    <span>{property.code}</span>
                    <Link href={`/assets/${property.id}`}>{title}</Link>
                    <small>
                        {portfolio} · {property.portfolio_code}
                    </small>
                </div>
                <div className="pmc-portfolio-control-card-badges">
                    {property.is_showcase ? (
                        <span>{t('portfolios.showcase')}</span>
                    ) : null}
                    <em>
                        {t(`portfolio_control.attention_${property.attention}`)}
                    </em>
                </div>
            </header>

            <dl>
                <div>
                    <dt>{t('portfolio_control.occupancy')}</dt>
                    <dd>{percent(property.occupancy_rate, locale)}</dd>
                </div>
                <div>
                    <dt>{t('portfolio_control.collection')}</dt>
                    <dd>{percent(property.collection_rate, locale)}</dd>
                </div>
                <div>
                    <dt>{t('portfolio_control.arrears')}</dt>
                    <dd>
                        {compactCurrency(
                            property.arrears,
                            locale,
                            property.currency,
                        )}
                    </dd>
                </div>
                <div>
                    <dt>{t('portfolio_control.net_cash_flow')}</dt>
                    <dd>
                        {compactCurrency(
                            property.net,
                            locale,
                            property.currency,
                        )}
                    </dd>
                </div>
            </dl>

            <div className="pmc-portfolio-control-pressure">
                {reasons.length === 0 ? (
                    <span className="is-clear">
                        <i className="bi bi-check-circle" aria-hidden="true" />
                        {t('portfolio_control.no_pressure')}
                    </span>
                ) : (
                    reasons.slice(0, 3).map((reason) => (
                        <span key={reason.key}>
                            <i
                                className={`bi ${reason.icon}`}
                                aria-hidden="true"
                            />
                            {t(reason.key, undefined, {
                                count: localizedNumber(reason.count, locale),
                            })}
                        </span>
                    ))
                )}
            </div>

            <footer>
                <Link
                    className="is-primary"
                    href={`/dashboard?property_id=${property.id}`}
                >
                    <i className="bi bi-grid-1x2" aria-hidden="true" />
                    {t('portfolio_control.focus_dashboard')}
                </Link>
                <Link href={`/action-center?property_id=${property.id}`}>
                    {t('nav.action_center')}
                </Link>
                <Link href={`/reports?property_id=${property.id}`}>
                    {t('nav.reports')}
                </Link>
            </footer>
        </article>
    );
}

function attentionReasons(property: PortfolioControlProperty) {
    const reasons: Array<{
        key: UiTranslationKey;
        count: number;
        icon: string;
    }> = [];

    if (property.arrears > 0) {
        reasons.push({
            key: 'portfolio_control.reason_arrears',
            count: 1,
            icon: 'bi-exclamation-circle',
        });
    }

    if (property.open_requests > 0) {
        reasons.push({
            key: 'portfolio_control.reason_maintenance',
            count: property.open_requests,
            icon: 'bi-tools',
        });
    }

    if (property.expiring_leases > 0) {
        reasons.push({
            key: 'portfolio_control.reason_expiring',
            count: property.expiring_leases,
            icon: 'bi-calendar-event',
        });
    }

    if (property.rentable_units > property.occupied_units) {
        reasons.push({
            key: 'portfolio_control.reason_vacant',
            count: property.rentable_units - property.occupied_units,
            icon: 'bi-building',
        });
    }

    return reasons;
}
