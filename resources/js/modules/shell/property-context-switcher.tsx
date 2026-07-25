import { router } from '@inertiajs/react';
import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';
import type { PropertyContext, PropertyContextOption } from '@/types';

export function PropertyContextSwitcher({
    context,
    currentUrl,
    collapsed,
    onExpand,
}: {
    context: PropertyContext;
    currentUrl: string;
    collapsed: boolean;
    onExpand: () => void;
}) {
    const { locale, t } = useTranslator();
    const [updating, setUpdating] = useState(false);
    const selectedLabel = context.selected
        ? optionLabel(context.selected, locale, context.options)
        : context.assignment_restricted
          ? t('shell.all_assigned_properties')
          : t('shell.all_properties');

    if (context.options.length === 0) {
        return null;
    }

    if (collapsed) {
        return (
            <button
                type="button"
                className="pmc-property-context-collapsed"
                aria-label={t('shell.open_property_scope', undefined, {
                    property: selectedLabel,
                })}
                title={selectedLabel}
                onClick={onExpand}
            >
                <i className="bi bi-buildings" aria-hidden="true" />
            </button>
        );
    }

    const changeProperty = (propertyId: string) => {
        const url = new URL(currentUrl, window.location.origin);

        url.searchParams.set('property_id', propertyId || 'all');
        url.searchParams.delete('page');

        if (propertyId) {
            url.searchParams.delete('portfolio_id');
        }

        setUpdating(true);
        router.get(
            url.pathname,
            Object.fromEntries(url.searchParams.entries()),
            {
                preserveScroll: true,
                preserveState: false,
                replace: true,
                onFinish: () => setUpdating(false),
            },
        );
    };

    return (
        <label className="pmc-property-context">
            <span>
                <i className="bi bi-buildings" aria-hidden="true" />
                {t('shell.property_scope')}
            </span>
            <select
                value={context.selected?.id ?? ''}
                disabled={updating}
                aria-busy={updating}
                onChange={(event) => changeProperty(event.currentTarget.value)}
            >
                <option value="">
                    {context.assignment_restricted
                        ? t('shell.all_assigned_properties')
                        : t('shell.all_properties')}
                </option>
                {context.options.map((property) => (
                    <option key={property.id} value={property.id}>
                        {optionLabel(property, locale, context.options)}
                    </option>
                ))}
            </select>
            <small>{t('shell.property_scope_help')}</small>
        </label>
    );
}

function optionLabel(
    property: PropertyContextOption,
    locale: string,
    options: PropertyContextOption[],
): string {
    const title =
        locale === 'ar'
            ? property.title_ar || property.title_en
            : property.title_en || property.title_ar;
    const portfolioCount = new Set(
        options.map((option) => option.portfolio_code).filter(Boolean),
    ).size;
    const portfolio =
        portfolioCount > 1 && property.portfolio_code
            ? ` · ${property.portfolio_code}`
            : '';

    return `${property.code} · ${title}${portfolio}`;
}
