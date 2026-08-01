import { useTranslator } from '@/lib/i18n';
import type { PropertyContext } from '@/types';

import { propertyLabel } from './property-context-options';
import type { PropertyContextGroup } from './property-context-options';

type PropertyContextResultsProps = {
    context: PropertyContext;
    groups: PropertyContextGroup[];
    resultCount: number;
    updating: boolean;
    allowAll: boolean;
    onSelect: (propertyId: string) => void;
};

export function PropertyContextResults({
    context,
    groups,
    resultCount,
    updating,
    allowAll,
    onSelect,
}: PropertyContextResultsProps) {
    const { locale, t } = useTranslator();
    const allLabel = context.assignment_restricted
        ? t('shell.all_assigned_properties')
        : t('shell.all_properties');

    return (
        <div
            className="pmc-property-picker-results"
            data-property-scope-results
        >
            {allowAll ? (
                <button
                    type="button"
                    className={`pmc-property-picker-option pmc-property-picker-all ${
                        context.selected ? '' : 'is-selected'
                    }`}
                    aria-pressed={!context.selected}
                    disabled={updating}
                    data-property-scope-clear
                    onClick={() => onSelect('')}
                >
                    <i className="bi bi-grid" aria-hidden="true" />
                    <span>
                        <strong>{allLabel}</strong>
                        <small>{t('shell.all_properties_help')}</small>
                    </span>
                    {!context.selected ? (
                        <i className="bi bi-check-lg" aria-hidden="true" />
                    ) : null}
                </button>
            ) : null}

            {groups.map((group) => (
                <section key={group.key}>
                    <h3>{group.label || t('shell.ungrouped_properties')}</h3>
                    {group.options.map((property) => {
                        const selected = context.selected?.id === property.id;

                        return (
                            <button
                                key={property.id}
                                type="button"
                                className={`pmc-property-picker-option ${
                                    selected ? 'is-selected' : ''
                                }`}
                                aria-pressed={selected}
                                disabled={updating}
                                data-property-scope-option={property.id}
                                onClick={() => onSelect(String(property.id))}
                            >
                                <i
                                    className="bi bi-building"
                                    aria-hidden="true"
                                />
                                <span>
                                    <strong>
                                        {propertyLabel(property, locale)}
                                    </strong>
                                    <small>{group.label}</small>
                                </span>
                                {selected ? (
                                    <i
                                        className="bi bi-check-lg"
                                        aria-hidden="true"
                                    />
                                ) : null}
                            </button>
                        );
                    })}
                </section>
            ))}

            {resultCount === 0 ? (
                <div className="pmc-property-picker-empty">
                    <i className="bi bi-search" aria-hidden="true" />
                    <strong>{t('shell.no_properties_found')}</strong>
                    <small>{t('shell.no_properties_found_help')}</small>
                </div>
            ) : null}
        </div>
    );
}
