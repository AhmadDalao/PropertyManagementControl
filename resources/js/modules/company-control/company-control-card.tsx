import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { compactCurrency, localizedNumber, percent } from '@/lib/utils';

import type { CompanyPortfolio } from './types';

export function CompanyControlCard({
    portfolio,
}: {
    portfolio: CompanyPortfolio;
}) {
    const { locale, t } = useTranslator();
    const title =
        locale === 'ar'
            ? portfolio.name_ar || portfolio.name_en
            : portfolio.name_en || portfolio.name_ar;
    const valuation = portfolio.valuation_totals
        .map((position) =>
            compactCurrency(position.amount, locale, position.currency),
        )
        .join(' · ');
    const arrears = portfolio.currency_totals
        .map((position) =>
            compactCurrency(position.arrears, locale, position.currency),
        )
        .join(' · ');
    const net = portfolio.currency_totals
        .map((position) =>
            compactCurrency(position.net, locale, position.currency),
        )
        .join(' · ');

    return (
        <article
            className={`pmc-company-control-card is-${portfolio.attention}`}
        >
            <header>
                <div>
                    <span>{portfolio.code}</span>
                    <Link href={`/portfolios/${portfolio.id}`}>{title}</Link>
                    <small>
                        {portfolio.owner?.name ||
                            t('company_control.owner_missing')}
                    </small>
                </div>
                <div className="pmc-company-control-card-badges">
                    {portfolio.is_showcase ? (
                        <span>{t('company_control.source_showcase')}</span>
                    ) : null}
                    <span>{t(`status.${portfolio.status}`)}</span>
                    <em>
                        {t(`company_control.attention_${portfolio.attention}`)}
                    </em>
                </div>
            </header>

            <div className="pmc-company-control-readiness">
                <div>
                    <span>{t('company_control.launch_readiness')}</span>
                    <strong>
                        {percent(portfolio.readiness.score, locale)}
                    </strong>
                </div>
                <div
                    className="pmc-company-control-progress"
                    role="progressbar"
                    aria-label={t('company_control.launch_readiness')}
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-valuenow={portfolio.readiness.score}
                >
                    <span style={{ width: `${portfolio.readiness.score}%` }} />
                </div>
                <small>
                    {portfolio.readiness.blocked > 0
                        ? t('company_control.blockers_count', undefined, {
                              count: localizedNumber(
                                  portfolio.readiness.blocked,
                                  locale,
                              ),
                          })
                        : t('company_control.readiness_status_ready')}
                </small>
            </div>

            <dl>
                <div>
                    <dt>{t('company_control.managed_value')}</dt>
                    <dd>{valuation || t('company_control.no_value')}</dd>
                </div>
                <div>
                    <dt>{t('company_control.occupancy')}</dt>
                    <dd>
                        {percent(portfolio.occupancy_rate, locale)}
                        <small>
                            {t('company_control.units_occupied', undefined, {
                                occupied: localizedNumber(
                                    portfolio.occupied_units,
                                    locale,
                                ),
                                total: localizedNumber(
                                    portfolio.rentable_units,
                                    locale,
                                ),
                            })}
                        </small>
                    </dd>
                </div>
                <div>
                    <dt>{t('company_control.arrears')}</dt>
                    <dd>{arrears || t('company_control.no_arrears')}</dd>
                </div>
                <div>
                    <dt>{t('company_control.net_cash_flow')}</dt>
                    <dd>{net || t('company_control.no_activity')}</dd>
                </div>
            </dl>

            <div className="pmc-company-control-facts">
                <span>
                    <i className="bi bi-buildings" aria-hidden="true" />
                    {t('company_control.properties_count', undefined, {
                        count: localizedNumber(portfolio.properties, locale),
                    })}
                </span>
                <span>
                    <i className="bi bi-file-earmark-text" aria-hidden="true" />
                    {t('company_control.leases_count', undefined, {
                        count: localizedNumber(portfolio.active_leases, locale),
                    })}
                </span>
                <span>
                    <i className="bi bi-people" aria-hidden="true" />
                    {t('company_control.accounts_count', undefined, {
                        count: localizedNumber(
                            portfolio.accounts.active,
                            locale,
                        ),
                    })}
                </span>
                <span>
                    <i className="bi bi-tools" aria-hidden="true" />
                    {t('company_control.requests_count', undefined, {
                        count: localizedNumber(portfolio.open_requests, locale),
                    })}
                </span>
            </div>

            <footer>
                <Link
                    className="is-primary"
                    href={`/portfolios/${portfolio.id}`}
                >
                    <i className="bi bi-arrow-up-right" aria-hidden="true" />
                    {t('company_control.open_portfolio')}
                </Link>
                <Link href={`/reports?portfolio_id=${portfolio.id}`}>
                    {t('nav.reports')}
                </Link>
                <Link href={`/system/readiness?portfolio_id=${portfolio.id}`}>
                    {t('nav.system_readiness')}
                </Link>
            </footer>
        </article>
    );
}
