import { Link } from '@inertiajs/react';

import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, localizedNumber, percent } from '@/lib/utils';

import type { OperationsDashboardProps } from '../types';

export function PropertyPerformanceGrid({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t } = useTranslator();
    const selectedProperty = props.propertyFocus.selected;

    if (props.propertyPerformance.length === 0) {
        return null;
    }

    return (
        <WorkspacePanel
            className="pmc-property-performance"
            eyebrow={t('dashboard.properties')}
            title={t('dashboard.property_performance')}
            description={t('dashboard.property_performance_description')}
            action={{
                label: selectedProperty
                    ? t('dashboard.open_focused_property')
                    : t('dashboard.open_portfolio_control'),
                href: selectedProperty
                    ? `/property-explorer?property_id=${selectedProperty.id}`
                    : '/portfolio-control',
            }}
        >
            <div className="pmc-property-performance-grid">
                {props.propertyPerformance.map((property) => (
                    <article
                        key={property.id}
                        className={`pmc-property-performance-card is-${property.attention}`}
                    >
                        <header>
                            <Link
                                href={`/property-explorer?property_id=${property.id}`}
                            >
                                <span>{property.code}</span>
                                <strong>
                                    {locale === 'ar'
                                        ? property.title_ar || property.title_en
                                        : property.title_en ||
                                          property.title_ar}
                                </strong>
                            </Link>
                            <em>
                                {t(`dashboard.attention_${property.attention}`)}
                            </em>
                        </header>

                        <dl>
                            <div>
                                <dt>{t('dashboard.occupancy_rate')}</dt>
                                <dd>
                                    {percent(property.occupancy_rate, locale)}
                                </dd>
                            </div>
                            <div>
                                <dt>{t('dashboard.collection_rate')}</dt>
                                <dd>
                                    {property.collection_rate === null
                                        ? t(
                                              'dashboard.currency_positions_count',
                                              undefined,
                                              {
                                                  count: localizedNumber(
                                                      property.currency_count,
                                                      locale,
                                                  ),
                                              },
                                          )
                                        : percent(
                                              property.collection_rate,
                                              locale,
                                          )}
                                </dd>
                            </div>
                            <div>
                                <dt>{t('dashboard.arrears')}</dt>
                                <dd>
                                    {property.currency_totals
                                        .map((position) =>
                                            currency(
                                                position.arrears,
                                                props.app.locale,
                                                position.currency,
                                            ),
                                        )
                                        .join(' · ')}
                                </dd>
                            </div>
                            <div>
                                <dt>{t('dashboard.net_cash_flow')}</dt>
                                <dd>
                                    {property.currency_totals
                                        .map((position) =>
                                            currency(
                                                position.net,
                                                props.app.locale,
                                                position.currency,
                                            ),
                                        )
                                        .join(' · ')}
                                </dd>
                            </div>
                        </dl>

                        <footer>
                            <span>
                                <i className="bi bi-tools" aria-hidden="true" />
                                {t('dashboard.open_service_count', undefined, {
                                    count: localizedNumber(
                                        property.open_requests,
                                        locale,
                                    ),
                                })}
                            </span>
                            <span>
                                <i
                                    className="bi bi-calendar-event"
                                    aria-hidden="true"
                                />
                                {t('dashboard.expiring_count', undefined, {
                                    count: localizedNumber(
                                        property.expiring_leases,
                                        locale,
                                    ),
                                })}
                            </span>
                            <Link
                                href={`/reports/properties/${property.id}`}
                                aria-label={t(
                                    'dashboard.open_property_report',
                                    undefined,
                                    {
                                        property:
                                            (locale === 'ar'
                                                ? property.title_ar ||
                                                  property.title_en
                                                : property.title_en ||
                                                  property.title_ar) ||
                                            property.code,
                                    },
                                )}
                            >
                                {t('dashboard.property_report')}
                                <i
                                    className="bi bi-arrow-up-right"
                                    aria-hidden="true"
                                />
                            </Link>
                        </footer>
                    </article>
                ))}
            </div>
        </WorkspacePanel>
    );
}
