import { useTranslator } from '@/lib/i18n';

import { statusLabel } from './map-utils';

type PropertyMapFiltersProps = {
    search: string;
    zone: string;
    occupancy: string;
    zones: string[];
    occupancyStates: string[];
    visibleCount: number;
    totalCount: number;
    hasFilters: boolean;
    onSearch: (value: string) => void;
    onZone: (value: string) => void;
    onOccupancy: (value: string) => void;
    onReset: () => void;
};

export function PropertyMapFilters({
    search,
    zone,
    occupancy,
    zones,
    occupancyStates,
    visibleCount,
    totalCount,
    hasFilters,
    onSearch,
    onZone,
    onOccupancy,
    onReset,
}: PropertyMapFiltersProps) {
    const { t } = useTranslator();

    return (
        <div className="pmc-property-map-filters">
            <label className="pmc-property-map-search">
                <span>{t('map.search')}</span>
                <div>
                    <i className="bi bi-search" />
                    <input
                        type="search"
                        className="form-control"
                        value={search}
                        placeholder={t('map.search_placeholder')}
                        onChange={(event) =>
                            onSearch(event.currentTarget.value)
                        }
                    />
                </div>
            </label>
            <label>
                <span>{t('map.zone')}</span>
                <select
                    className="form-select"
                    value={zone}
                    onChange={(event) => onZone(event.currentTarget.value)}
                >
                    <option value="all">{t('map.all_zones')}</option>
                    {zones.map((option) => (
                        <option key={option} value={option}>
                            {option}
                        </option>
                    ))}
                </select>
            </label>
            <label>
                <span>{t('map.occupancy')}</span>
                <select
                    className="form-select"
                    value={occupancy}
                    onChange={(event) => onOccupancy(event.currentTarget.value)}
                >
                    <option value="all">{t('map.all_occupancy')}</option>
                    {occupancyStates.map((state) => (
                        <option key={state} value={state}>
                            {statusLabel(state, t)}
                        </option>
                    ))}
                </select>
            </label>
            <div className="pmc-property-map-results" aria-live="polite">
                <span>
                    <strong>{visibleCount}</strong>
                    {t('map.results', undefined, { total: totalCount })}
                </span>
                {hasFilters ? (
                    <button
                        type="button"
                        className="btn btn-outline-secondary"
                        onClick={onReset}
                    >
                        {t('actions.reset')}
                    </button>
                ) : null}
            </div>
        </div>
    );
}
