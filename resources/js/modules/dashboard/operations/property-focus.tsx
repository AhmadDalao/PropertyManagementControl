import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { OperationsDashboardProps, PropertyFocusOption } from '../types';

export function PropertyFocus({
    focus,
}: {
    focus: OperationsDashboardProps['propertyFocus'];
}) {
    const { locale, t } = useTranslator();
    const selectedTitle = focus.selected
        ? propertyTitle(focus.selected, locale)
        : focus.assignment_restricted
          ? t('dashboard.all_assigned_properties')
          : t('dashboard.all_properties');

    if (focus.assignment_restricted && !focus.has_assignments) {
        return (
            <section className="pmc-dashboard-assignment-empty">
                <span className="pmc-dashboard-assignment-icon">
                    <i className="bi bi-building" aria-hidden="true" />
                </span>
                <div>
                    <span>{t('dashboard.assigned_scope')}</span>
                    <strong>{t('dashboard.no_assigned_properties')}</strong>
                    <small>{t('dashboard.no_assigned_properties_help')}</small>
                </div>
                <Link href="/portfolios">
                    {t('dashboard.review_portfolio')}
                    <i className="bi bi-arrow-up-right" aria-hidden="true" />
                </Link>
            </section>
        );
    }

    return (
        <section
            className="pmc-dashboard-focus"
            aria-labelledby="dashboard-property-focus-title"
        >
            <span className="pmc-dashboard-focus-icon">
                <i className="bi bi-building-check" aria-hidden="true" />
            </span>
            <div className="pmc-dashboard-focus-copy">
                <span>{t('dashboard.property_focus_eyebrow')}</span>
                <strong id="dashboard-property-focus-title">
                    {selectedTitle}
                </strong>
                <small>
                    {focus.selected
                        ? t('dashboard.property_focus_active_description')
                        : t(
                              'dashboard.property_focus_all_description',
                              undefined,
                              {
                                  count: localizedNumber(
                                      focus.property_count,
                                      locale,
                                  ),
                              },
                          )}
                </small>
            </div>

            {focus.selected ? (
                <Link
                    className="pmc-dashboard-focus-action"
                    href={`/assets/${focus.selected.id}`}
                >
                    {t('dashboard.open_focused_property')}
                    <i className="bi bi-arrow-up-right" aria-hidden="true" />
                </Link>
            ) : (
                <span className="pmc-dashboard-focus-hint">
                    <i className="bi bi-layout-sidebar" aria-hidden="true" />
                    {t('shell.property_scope_help')}
                </span>
            )}
        </section>
    );
}

function propertyTitle(property: PropertyFocusOption, locale: string): string {
    return locale === 'ar'
        ? property.title_ar || property.title_en
        : property.title_en || property.title_ar || property.code;
}
