import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { OperationsDashboardProps, PropertyFocusOption } from '../types';

export function PropertyFocus({
    focus,
}: {
    focus: OperationsDashboardProps['propertyFocus'];
}) {
    const { locale, t } = useTranslator();
    const [updating, setUpdating] = useState(false);
    const selectedTitle = focus.selected
        ? propertyTitle(focus.selected, locale)
        : focus.assignment_restricted
          ? t('dashboard.all_assigned_properties')
          : t('dashboard.all_properties');

    const changeProperty = (value: string) => {
        setUpdating(true);
        router.get(
            '/dashboard',
            value === '' ? {} : { property_id: Number(value) },
            {
                preserveState: true,
                replace: true,
                onFinish: () => setUpdating(false),
            },
        );
    };

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
            aria-busy={updating}
        >
            <div>
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
                                      focus.options.length,
                                      locale,
                                  ),
                              },
                          )}
                </small>
            </div>

            <div className="pmc-dashboard-focus-controls">
                <label htmlFor="dashboard-property-focus">
                    {t('dashboard.property_focus_label')}
                </label>
                <select
                    id="dashboard-property-focus"
                    value={focus.selected?.id ?? ''}
                    disabled={updating}
                    onChange={(event) => changeProperty(event.target.value)}
                >
                    <option value="">
                        {focus.assignment_restricted
                            ? t('dashboard.all_assigned_properties')
                            : t('dashboard.all_properties')}
                    </option>
                    {focus.options.map((property) => (
                        <option key={property.id} value={property.id}>
                            {property.code} · {propertyTitle(property, locale)}
                        </option>
                    ))}
                </select>
                {focus.selected ? (
                    <Link href={`/assets/${focus.selected.id}`}>
                        {t('dashboard.open_focused_property')}
                        <i
                            className="bi bi-arrow-up-right"
                            aria-hidden="true"
                        />
                    </Link>
                ) : null}
            </div>

            <span className="visually-hidden" role="status" aria-live="polite">
                {updating ? t('dashboard.updating_property_focus') : ''}
            </span>
        </section>
    );
}

function propertyTitle(property: PropertyFocusOption, locale: string): string {
    return locale === 'ar'
        ? property.title_ar || property.title_en
        : property.title_en || property.title_ar || property.code;
}
