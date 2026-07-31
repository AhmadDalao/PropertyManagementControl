import { useTranslator } from '@/lib/i18n';

import type { PropertyExplorerPayload } from './types';
import { useExplorerQuery } from './use-explorer-query';

export function ExplorerControls({
    explorer,
}: {
    explorer: PropertyExplorerPayload;
}) {
    const { locale, t } = useTranslator();
    const query = useExplorerQuery(explorer.filters);

    return (
        <section
            className={`pmc-explorer-controls ${query.pending ? 'is-loading' : ''}`}
            aria-label={t('assets.explorer.filters')}
        >
            <label>
                <span>{t('assets.explorer.property')}</span>
                <select
                    className="form-select"
                    data-testid="property-explorer-property"
                    value={explorer.filters.property_id ?? ''}
                    onChange={(event) =>
                        query.selectProperty(Number(event.currentTarget.value))
                    }
                >
                    {explorer.properties.map((property) => (
                        <option key={property.id} value={property.id}>
                            {localizedTitle(property, locale)} · {property.code}
                        </option>
                    ))}
                </select>
            </label>

            <label className="pmc-explorer-search">
                <span>{t('assets.explorer.search')}</span>
                <div>
                    <i className="bi bi-search" aria-hidden="true" />
                    <input
                        type="search"
                        value={query.search}
                        data-testid="property-explorer-search"
                        placeholder={t('assets.explorer.search_placeholder')}
                        onChange={(event) =>
                            query.setSearch(event.currentTarget.value)
                        }
                    />
                    {query.pending ? (
                        <span
                            className="spinner-border spinner-border-sm"
                            aria-label={t('table.searching')}
                        />
                    ) : null}
                </div>
            </label>

            <ExplorerSelect
                label={t('assets.type')}
                value={explorer.filters.asset_type}
                options={[
                    'all',
                    'property',
                    'building',
                    'floor',
                    'unit',
                    'space',
                ]}
                optionLabel={(value) =>
                    value === 'all'
                        ? t('assets.all')
                        : t(`assets.types.${value}`)
                }
                onChange={(value) =>
                    query.visit({ asset_type: value, page: 1 })
                }
            />
            <ExplorerSelect
                label={t('assets.occupancy')}
                value={explorer.filters.occupancy_status}
                options={[
                    'all',
                    'vacant',
                    'occupied',
                    'partially_occupied',
                    'reserved',
                    'maintenance',
                ]}
                optionLabel={(value) =>
                    value === 'all' ? t('assets.all') : t(`status.${value}`)
                }
                onChange={(value) =>
                    query.visit({ occupancy_status: value, page: 1 })
                }
            />

            <button
                type="button"
                className="btn btn-light"
                onClick={query.reset}
            >
                <i
                    className="bi bi-arrow-counterclockwise"
                    aria-hidden="true"
                />
                {t('actions.reset')}
            </button>
        </section>
    );
}

function ExplorerSelect({
    label,
    value,
    options,
    optionLabel,
    onChange,
}: {
    label: string;
    value: string;
    options: string[];
    optionLabel: (value: string) => string;
    onChange: (value: string) => void;
}) {
    return (
        <label>
            <span>{label}</span>
            <select
                className="form-select"
                value={value}
                onChange={(event) => onChange(event.currentTarget.value)}
            >
                {options.map((option) => (
                    <option key={option} value={option}>
                        {optionLabel(option)}
                    </option>
                ))}
            </select>
        </label>
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
