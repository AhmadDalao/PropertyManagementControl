import { Link } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';

import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import type { PropertyMapAsset, PropertyMapSummary } from './types';

type PropertyMapWorkspaceProps = {
    assets: PropertyMapAsset[];
    summary: PropertyMapSummary;
    toolbar?: ReactNode;
};

export function PropertyMapWorkspace({
    assets,
    summary,
    toolbar,
}: PropertyMapWorkspaceProps) {
    const { locale, t } = useTranslator();
    const [selectedAssetId, setSelectedAssetId] = useState<number | null>(
        assets.find((asset) => asset.has_coordinates)?.id ??
            assets[0]?.id ??
            null,
    );
    const [search, setSearch] = useState('');
    const [zone, setZone] = useState('all');
    const [occupancy, setOccupancy] = useState('all');
    const normalizedSearch = search.trim().toLocaleLowerCase(locale);
    const zones = uniqueValues(assets.map((asset) => asset.zone));
    const occupancyStates = uniqueValues(
        assets.map((asset) => asset.occupancy_status),
    );
    const visibleAssets = assets.filter((asset) => {
        const matchesZone = zone === 'all' || asset.zone === zone;
        const matchesOccupancy =
            occupancy === 'all' || asset.occupancy_status === occupancy;
        const matchesSearch =
            normalizedSearch === '' ||
            [
                asset.title,
                asset.code,
                asset.zone,
                asset.land_number,
                asset.address,
                asset.owner,
                asset.manager,
            ].some((value) =>
                String(value ?? '')
                    .toLocaleLowerCase(locale)
                    .includes(normalizedSearch),
            );

        return matchesZone && matchesOccupancy && matchesSearch;
    });
    const positionedAssets = visibleAssets.filter(
        (asset) => asset.has_coordinates,
    );
    const setupAssets = assets.filter(
        (asset) => !asset.has_coordinates || !asset.has_identity,
    );
    const selectedAsset =
        visibleAssets.find((asset) => asset.id === selectedAssetId) ??
        positionedAssets[0] ??
        visibleAssets[0] ??
        null;
    const totalValue = assets.reduce(
        (sum, asset) => sum + Number(asset.valuation_amount ?? 0),
        0,
    );
    const hasFilters =
        normalizedSearch !== '' || zone !== 'all' || occupancy !== 'all';

    const resetFilters = () => {
        setSearch('');
        setZone('all');
        setOccupancy('all');
    };

    return (
        <section
            className="pmc-property-map-workspace"
            data-testid="property-map-workspace"
        >
            <header className="pmc-property-map-head">
                <div>
                    <span>{t('map.workspace_eyebrow')}</span>
                    <h2>{t('map.workspace_title')}</h2>
                    <p>{t('map.workspace_description')}</p>
                </div>
                {toolbar ? (
                    <div className="pmc-property-map-toolbar">{toolbar}</div>
                ) : null}
            </header>

            <div className="pmc-property-map-metrics" role="list">
                <MapMetric
                    icon="bi-buildings"
                    label={t('map.properties')}
                    value={summary.total}
                    detail={t('map.properties_detail', undefined, {
                        count: summary.zones.length,
                    })}
                />
                <MapMetric
                    icon="bi-check2-circle"
                    label={t('map.map_ready')}
                    value={`${summary.coverage_percent}%`}
                    detail={t('map.ready_detail', undefined, {
                        ready: summary.ready,
                        total: summary.total,
                    })}
                    tone="teal"
                />
                <MapMetric
                    icon="bi-geo-alt"
                    label={t('map.positioned')}
                    value={summary.mapped}
                    detail={t('map.positioned_detail', undefined, {
                        total: summary.total,
                    })}
                />
                <MapMetric
                    icon="bi-cash-stack"
                    label={t('map.mapped_value')}
                    value={currency(totalValue, locale)}
                    detail={t('map.value_detail')}
                />
            </div>

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
                                setSearch(event.currentTarget.value)
                            }
                        />
                    </div>
                </label>
                <label>
                    <span>{t('map.zone')}</span>
                    <select
                        className="form-select"
                        value={zone}
                        onChange={(event) => setZone(event.currentTarget.value)}
                    >
                        <option value="all">{t('map.all_zones')}</option>
                        {zones.map((zoneOption) => (
                            <option key={zoneOption} value={zoneOption}>
                                {zoneOption}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    <span>{t('map.occupancy')}</span>
                    <select
                        className="form-select"
                        value={occupancy}
                        onChange={(event) =>
                            setOccupancy(event.currentTarget.value)
                        }
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
                        <strong>{visibleAssets.length}</strong>
                        {t('map.results', undefined, {
                            total: assets.length,
                        })}
                    </span>
                    <button
                        type="button"
                        className="btn btn-outline-secondary"
                        disabled={!hasFilters}
                        onClick={resetFilters}
                    >
                        <i className="bi bi-arrow-counterclockwise" />
                        {t('actions.reset')}
                    </button>
                </div>
            </div>

            {setupAssets.length > 0 ? (
                <div className="pmc-property-map-setup" role="status">
                    <span>
                        <i className="bi bi-exclamation-triangle" />
                    </span>
                    <div>
                        <strong>
                            {t('map.setup_title', undefined, {
                                count: setupAssets.length,
                            })}
                        </strong>
                        <p>{t('map.setup_description')}</p>
                    </div>
                    <Link
                        href={setupAssets[0].edit_href}
                        className="btn btn-light"
                    >
                        {t('map.complete_setup')}
                        <i className="bi bi-arrow-right" />
                    </Link>
                </div>
            ) : (
                <div className="pmc-property-map-setup is-ready" role="status">
                    <span>
                        <i className="bi bi-check-circle" />
                    </span>
                    <div>
                        <strong>{t('map.setup_complete')}</strong>
                        <p>{t('map.setup_complete_description')}</p>
                    </div>
                </div>
            )}

            <div className="pmc-property-map-layout">
                <div className="pmc-property-map-stage">
                    <header>
                        <div>
                            <span>{t('map.canvas_eyebrow')}</span>
                            <strong>{t('map.canvas_title')}</strong>
                        </div>
                        <div className="pmc-property-map-legend">
                            <span className="is-occupied">
                                {t('status.occupied')}
                            </span>
                            <span className="is-vacant">
                                {t('status.vacant')}
                            </span>
                        </div>
                    </header>
                    <div
                        className="pmc-property-map-canvas"
                        data-testid="property-map-canvas"
                        data-positioned-count={positionedAssets.length}
                        aria-label={t('map.canvas_title')}
                    >
                        <div className="pmc-property-map-grid" />
                        <div className="pmc-property-map-road is-one" />
                        <div className="pmc-property-map-road is-two" />

                        {positionedAssets.map((asset) => (
                            <button
                                key={asset.id}
                                type="button"
                                className={`pmc-property-map-marker is-${asset.occupancy_status} ${
                                    selectedAsset?.id === asset.id
                                        ? 'is-selected'
                                        : ''
                                }`}
                                data-testid="property-map-marker"
                                style={{
                                    insetInlineStart: `${clamp(asset.x, 8, 84)}%`,
                                    top: `${clamp(asset.y, 10, 88)}%`,
                                }}
                                aria-label={t(
                                    'map.select_property',
                                    undefined,
                                    {
                                        property: asset.title,
                                    },
                                )}
                                aria-pressed={selectedAsset?.id === asset.id}
                                onClick={() => setSelectedAssetId(asset.id)}
                            >
                                <span>{asset.land_number ?? asset.code}</span>
                                <strong>{asset.title}</strong>
                            </button>
                        ))}

                        {positionedAssets.length === 0 ? (
                            <div className="pmc-property-map-empty">
                                <span>
                                    <i className="bi bi-geo-alt" />
                                </span>
                                <strong>{t('map.empty_title')}</strong>
                                <p>{t('map.empty_description')}</p>
                                {setupAssets[0] ? (
                                    <Link
                                        href={setupAssets[0].edit_href}
                                        className="btn btn-primary"
                                    >
                                        {t('map.add_first_position')}
                                    </Link>
                                ) : null}
                            </div>
                        ) : null}
                    </div>
                </div>

                <PropertyMapDetail
                    asset={selectedAsset}
                    onReset={resetFilters}
                />
            </div>

            <div
                className="pmc-property-map-directory"
                data-testid="property-map-directory"
            >
                <header>
                    <div>
                        <span>{t('map.directory_eyebrow')}</span>
                        <h3>{t('map.directory_title')}</h3>
                        <p>{t('map.directory_description')}</p>
                    </div>
                    <strong>
                        {t('map.record_count', undefined, {
                            count: visibleAssets.length,
                        })}
                    </strong>
                </header>

                {visibleAssets.length > 0 ? (
                    <div className="pmc-property-map-records">
                        {visibleAssets.map((asset) => (
                            <article
                                key={asset.id}
                                data-testid="property-map-record"
                                className={
                                    selectedAsset?.id === asset.id
                                        ? 'is-selected'
                                        : ''
                                }
                            >
                                <button
                                    type="button"
                                    onClick={() => setSelectedAssetId(asset.id)}
                                >
                                    <span>
                                        {asset.zone ?? t('map.no_zone')}
                                    </span>
                                    <strong>{asset.title}</strong>
                                    <small>
                                        {asset.land_number ??
                                            t('map.no_land_number')}{' '}
                                        · {asset.code}
                                    </small>
                                </button>
                                <span
                                    className={`pmc-property-map-record-status ${
                                        asset.has_coordinates &&
                                        asset.has_identity
                                            ? 'is-ready'
                                            : 'is-setup'
                                    }`}
                                >
                                    {asset.has_coordinates && asset.has_identity
                                        ? t('map.ready')
                                        : t('map.needs_setup')}
                                </span>
                                <Link
                                    href={asset.href}
                                    aria-label={t(
                                        'map.open_property_named',
                                        undefined,
                                        {
                                            property: asset.title,
                                        },
                                    )}
                                >
                                    <i className="bi bi-arrow-right" />
                                </Link>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="pmc-property-map-no-results">
                        <strong>{t('map.no_results')}</strong>
                        <p>{t('map.no_results_description')}</p>
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={resetFilters}
                        >
                            {t('map.show_all')}
                        </button>
                    </div>
                )}
            </div>
        </section>
    );
}

function MapMetric({
    icon,
    label,
    value,
    detail,
    tone = 'default',
}: {
    icon: string;
    label: string;
    value: string | number;
    detail: string;
    tone?: 'default' | 'teal';
}) {
    return (
        <article
            className={`pmc-property-map-metric is-${tone}`}
            role="listitem"
        >
            <span>
                <i className={`bi ${icon}`} />
            </span>
            <div>
                <small>{label}</small>
                <strong>{value}</strong>
                <p>{detail}</p>
            </div>
        </article>
    );
}

function PropertyMapDetail({
    asset,
    onReset,
}: {
    asset: PropertyMapAsset | null;
    onReset: () => void;
}) {
    const { locale, t } = useTranslator();

    if (!asset) {
        return (
            <aside
                className="pmc-property-map-detail"
                data-testid="property-map-detail"
            >
                <div className="pmc-property-map-detail-empty">
                    <span>
                        <i className="bi bi-search" />
                    </span>
                    <strong>{t('map.no_selection')}</strong>
                    <p>{t('map.no_selection_description')}</p>
                    <button
                        type="button"
                        className="btn btn-primary"
                        onClick={onReset}
                    >
                        {t('map.show_all')}
                    </button>
                </div>
            </aside>
        );
    }

    const isReady = asset.has_coordinates && asset.has_identity;

    return (
        <aside
            className="pmc-property-map-detail"
            data-testid="property-map-detail"
        >
            <header>
                <div>
                    <span>{asset.zone ?? t('map.no_zone')}</span>
                    <h3>{asset.title}</h3>
                    <p>
                        {asset.land_number ?? t('map.no_land_number')} ·{' '}
                        {asset.code}
                    </p>
                </div>
                <em className={isReady ? 'is-ready' : 'is-setup'}>
                    {isReady ? t('map.ready') : t('map.needs_setup')}
                </em>
            </header>

            <p className="pmc-property-map-address">
                <i className="bi bi-geo-alt" />
                {asset.address ?? t('map.no_address')}
            </p>

            <dl className="pmc-property-map-facts">
                <div>
                    <dt>{t('map.value')}</dt>
                    <dd>
                        {currency(
                            asset.valuation_amount,
                            locale,
                            asset.currency,
                        )}
                    </dd>
                </div>
                <div>
                    <dt>{t('map.occupancy')}</dt>
                    <dd>{statusLabel(asset.occupancy_status, t)}</dd>
                </div>
                <div>
                    <dt>{t('map.units')}</dt>
                    <dd>{asset.rentable_children_count}</dd>
                </div>
                <div>
                    <dt>{t('map.open_issues')}</dt>
                    <dd>{asset.open_requests_count}</dd>
                </div>
            </dl>

            <div className="pmc-property-map-responsibility">
                <div>
                    <span>{t('map.owner')}</span>
                    <strong>{asset.owner ?? t('map.not_assigned')}</strong>
                </div>
                <div>
                    <span>{t('map.manager')}</span>
                    <strong>{asset.manager ?? t('map.not_assigned')}</strong>
                </div>
                <div>
                    <span>{t('map.active_leases')}</span>
                    <strong>{asset.active_leases_count}</strong>
                </div>
                <div>
                    <span>{t('map.coordinates')}</span>
                    <strong>
                        {asset.has_coordinates &&
                        asset.latitude !== null &&
                        asset.latitude !== undefined &&
                        asset.longitude !== null &&
                        asset.longitude !== undefined
                            ? `${asset.latitude}, ${asset.longitude}`
                            : asset.has_coordinates
                              ? t('map.manual_position')
                              : t('map.not_set')}
                    </strong>
                </div>
            </div>

            <div className="pmc-property-map-detail-actions">
                <Link href={asset.href} className="btn btn-primary">
                    {t('map.open_property')}
                </Link>
                <Link
                    href={asset.edit_href}
                    className="btn btn-outline-secondary"
                >
                    {t('map.edit_map_data')}
                </Link>
            </div>
        </aside>
    );
}

function uniqueValues(values: Array<string | null | undefined>): string[] {
    return Array.from(
        new Set(
            values.filter(
                (value): value is string =>
                    typeof value === 'string' && value.trim() !== '',
            ),
        ),
    ).sort((first, second) => first.localeCompare(second));
}

function statusLabel(
    status: string,
    t: ReturnType<typeof useTranslator>['t'],
): string {
    return t(
        `status.${status}`,
        status
            .replaceAll('_', ' ')
            .replace(/\b\w/g, (letter) => letter.toUpperCase()),
    );
}

function clamp(value: number, minimum: number, maximum: number): number {
    return Math.max(minimum, Math.min(maximum, value));
}
