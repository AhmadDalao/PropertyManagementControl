import { useTranslator } from '@/lib/i18n';
import type { PropertyContext } from '@/types';

import { portfolioTitle, propertyLabel } from './property-context-options';
import { PropertyContextPicker } from './property-context-picker';
import { usePropertyContextSwitcher } from './use-property-context-switcher';

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
    const {
        changeProperty,
        closePicker,
        openPicker,
        pickerOpen,
        search,
        setSearch,
        triggerRef,
        updating,
    } = usePropertyContextSwitcher(context, currentUrl);
    const selectedLabel = context.selected
        ? propertyLabel(context.selected, locale)
        : context.assignment_restricted
          ? t('shell.all_assigned_properties')
          : t('shell.all_properties');
    const selectedPortfolio = context.selected
        ? portfolioTitle(context.selected, locale)
        : '';

    if (context.options.length === 0) {
        return null;
    }

    if (collapsed) {
        return (
            <button
                ref={triggerRef}
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

    return (
        <section className="pmc-property-context" aria-busy={updating}>
            <span>
                <i className="bi bi-buildings" aria-hidden="true" />
                {t('shell.property_scope')}
            </span>
            <button
                ref={triggerRef}
                type="button"
                className="pmc-property-context-trigger"
                aria-haspopup="dialog"
                aria-expanded={pickerOpen}
                aria-label={t('shell.open_property_scope', undefined, {
                    property: selectedLabel,
                })}
                disabled={updating}
                data-property-scope-trigger
                data-selected-property={context.selected?.id ?? 'all'}
                onClick={openPicker}
            >
                <i className="bi bi-building" aria-hidden="true" />
                <span>
                    <strong>{selectedLabel}</strong>
                    <small>
                        {selectedPortfolio ||
                            t('shell.available_properties', undefined, {
                                count: context.options.length,
                            })}
                    </small>
                </span>
                <i className="bi bi-chevron-down" aria-hidden="true" />
            </button>
            <small>{t('shell.property_scope_help')}</small>
            <PropertyContextPicker
                context={context}
                open={pickerOpen}
                search={search}
                updating={updating}
                onSearch={setSearch}
                onSelect={changeProperty}
                onClose={closePicker}
            />
        </section>
    );
}
