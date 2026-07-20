import { Link } from '@inertiajs/react';
import L from 'leaflet';
import 'leaflet.markercluster';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';

import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';

import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import type {
    PropertyMapAsset,
    PropertyMapConfig,
    PropertyMapSummary,
} from './types';

type PropertyMapWorkspaceProps = {
    assets: PropertyMapAsset[];
    summary: PropertyMapSummary;
    config: PropertyMapConfig;
    toolbar?: ReactNode;
};

export function PropertyMapWorkspace({
    assets,
    summary,
    config,
    toolbar,
}: PropertyMapWorkspaceProps) {
    const { direction, locale, t } = useTranslator();
    const [selectedAssetId, setSelectedAssetId] = useState<number | null>(null);
    const [search, setSearch] = useState('');
    const [zone, setZone] = useState('all');
    const [occupancy, setOccupancy] = useState('all');
    const [page, setPage] = useState(1);
    const [fitRequest, setFitRequest] = useState(0);
    const normalizedSearch = search.trim().toLocaleLowerCase(locale);
    const zones = uniqueValues(
        assets.map((asset) => asset.zone),
        locale,
    );
    const occupancyStates = uniqueValues(
        assets.map((asset) => asset.occupancy_status),
        locale,
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
                asset.portfolio,
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
        (asset) =>
            asset.has_coordinates &&
            asset.latitude !== null &&
            asset.longitude !== null,
    );
    const pageSize = config.directory_page_size || 12;
    const lastPage = Math.max(1, Math.ceil(visibleAssets.length / pageSize));
    const safePage = Math.min(page, lastPage);
    const pageAssets = visibleAssets.slice(
        (safePage - 1) * pageSize,
        safePage * pageSize,
    );
    const setupAssets = assets.filter((asset) => !asset.map_ready);
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

    const selectAsset = (asset: PropertyMapAsset) => {
        setSelectedAssetId(asset.id);
        const resultIndex = visibleAssets.findIndex(
            (record) => record.id === asset.id,
        );

        if (resultIndex >= 0) {
            setPage(Math.floor(resultIndex / pageSize) + 1);
        }
    };

    const resetFilters = () => {
        setSearch('');
        setZone('all');
        setOccupancy('all');
        setPage(1);
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
                            onChange={(event) => {
                                setSearch(event.currentTarget.value);
                                setPage(1);
                            }}
                        />
                    </div>
                </label>
                <label>
                    <span>{t('map.zone')}</span>
                    <select
                        className="form-select"
                        value={zone}
                        onChange={(event) => {
                            setZone(event.currentTarget.value);
                            setPage(1);
                        }}
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
                        onChange={(event) => {
                            setOccupancy(event.currentTarget.value);
                            setPage(1);
                        }}
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
                    {hasFilters ? (
                        <button
                            type="button"
                            className="btn btn-outline-secondary"
                            onClick={resetFilters}
                        >
                            {t('actions.reset')}
                        </button>
                    ) : null}
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
                        className="btn btn-outline-secondary"
                    >
                        {t('map.complete_setup')}
                        <i className="bi bi-arrow-right" />
                    </Link>
                </div>
            ) : assets.length > 0 ? (
                <div className="pmc-property-map-setup is-ready" role="status">
                    <span>
                        <i className="bi bi-check2-circle" />
                    </span>
                    <div>
                        <strong>{t('map.setup_complete')}</strong>
                        <p>{t('map.setup_complete_description')}</p>
                    </div>
                </div>
            ) : null}

            <div className="pmc-property-map-layout">
                <div className="pmc-property-map-stage">
                    <header>
                        <div>
                            <span>{t('map.map_provider')}</span>
                            <strong>{t('map.canvas_title')}</strong>
                        </div>
                        <button
                            type="button"
                            className="btn btn-sm btn-outline-secondary"
                            onClick={() => setFitRequest((value) => value + 1)}
                            disabled={positionedAssets.length === 0}
                        >
                            <i className="bi bi-arrows-fullscreen" />
                            {t('map.fit_results')}
                        </button>
                    </header>
                    <GeographicMap
                        assets={positionedAssets}
                        selectedAssetId={selectedAssetId}
                        config={config}
                        direction={direction}
                        fitRequest={fitRequest}
                        onSelect={selectAsset}
                    />
                    {positionedAssets.length === 0 ? (
                        <div className="pmc-property-map-empty">
                            <span>
                                <i className="bi bi-geo-alt" />
                            </span>
                            <strong>{t('map.empty_title')}</strong>
                            <p>{t('map.empty_description')}</p>
                            {assets[0] ? (
                                <Link
                                    href={assets[0].edit_href}
                                    className="btn btn-primary"
                                >
                                    {t('map.add_first_position')}
                                </Link>
                            ) : null}
                        </div>
                    ) : null}
                </div>

                <PropertyMapDetail asset={selectedAsset} />
            </div>

            <section
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

                {pageAssets.length > 0 ? (
                    <>
                        <div className="pmc-property-map-records">
                            {pageAssets.map((asset) => (
                                <article
                                    key={asset.id}
                                    className={
                                        selectedAsset?.id === asset.id
                                            ? 'is-selected'
                                            : ''
                                    }
                                    data-testid="property-map-record"
                                >
                                    <button
                                        type="button"
                                        onClick={() => selectAsset(asset)}
                                    >
                                        <span>
                                            {asset.zone ?? t('map.no_zone')}
                                        </span>
                                        <strong>{asset.title}</strong>
                                        <small>
                                            {asset.code} ·{' '}
                                            {asset.land_number ??
                                                t('map.no_land_number')}
                                        </small>
                                    </button>
                                    {asset.is_showcase ? (
                                        <span className="pmc-showcase-badge">
                                            {t('map.showcase')}
                                        </span>
                                    ) : null}
                                    <span
                                        className={`pmc-property-map-record-status ${asset.map_ready ? 'is-ready' : ''}`}
                                    >
                                        {asset.map_ready
                                            ? t('map.ready')
                                            : t('map.needs_setup')}
                                    </span>
                                    <Link
                                        href={asset.href}
                                        aria-label={t(
                                            'map.open_property_named',
                                            undefined,
                                            { property: asset.title },
                                        )}
                                    >
                                        <i className="bi bi-arrow-up-right" />
                                    </Link>
                                </article>
                            ))}
                        </div>
                        {lastPage > 1 ? (
                            <nav
                                className="pmc-property-map-pagination"
                                aria-label={t('map.directory_title')}
                            >
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary"
                                    disabled={safePage === 1}
                                    onClick={() =>
                                        setPage((value) =>
                                            Math.max(1, value - 1),
                                        )
                                    }
                                >
                                    {t('map.previous_records')}
                                </button>
                                <span>
                                    {t('map.directory_page', undefined, {
                                        page: safePage,
                                        pages: lastPage,
                                    })}
                                </span>
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary"
                                    disabled={safePage === lastPage}
                                    onClick={() =>
                                        setPage((value) =>
                                            Math.min(lastPage, value + 1),
                                        )
                                    }
                                >
                                    {t('map.next_records')}
                                </button>
                            </nav>
                        ) : null}
                    </>
                ) : (
                    <div className="pmc-property-map-no-results">
                        <strong>{t('map.no_results')}</strong>
                        <p>{t('map.no_results_description')}</p>
                        <button
                            type="button"
                            className="btn btn-outline-secondary"
                            onClick={resetFilters}
                        >
                            {t('map.show_all')}
                        </button>
                    </div>
                )}
            </section>
        </section>
    );
}

function GeographicMap({
    assets,
    selectedAssetId,
    config,
    direction,
    fitRequest,
    onSelect,
}: {
    assets: PropertyMapAsset[];
    selectedAssetId: number | null;
    config: PropertyMapConfig;
    direction: 'ltr' | 'rtl';
    fitRequest: number;
    onSelect: (asset: PropertyMapAsset) => void;
}) {
    const { t } = useTranslator();
    const containerRef = useRef<HTMLDivElement | null>(null);
    const mapRef = useRef<L.Map | null>(null);
    const clusterRef = useRef<L.MarkerClusterGroup | null>(null);
    const markerRef = useRef(new Map<number, L.Marker>());
    const onSelectRef = useRef(onSelect);
    const [tilesFailed, setTilesFailed] = useState(false);

    useEffect(() => {
        onSelectRef.current = onSelect;
    }, [onSelect]);

    useEffect(() => {
        if (!containerRef.current || mapRef.current) {
            return;
        }

        const map = L.map(containerRef.current, {
            center: config.default_center,
            zoom: config.default_zoom,
            zoomControl: false,
            preferCanvas: true,
        });
        const zoom = L.control.zoom({
            position: direction === 'rtl' ? 'topright' : 'topleft',
        });
        const tileLayer = L.tileLayer(config.tile_url, {
            attribution: config.attribution,
            maxZoom: 19,
            updateWhenIdle: true,
            keepBuffer: 1,
        });
        const cluster = L.markerClusterGroup({
            chunkedLoading: true,
            maxClusterRadius: 48,
            showCoverageOnHover: false,
            iconCreateFunction: (group) =>
                L.divIcon({
                    className: 'pmc-map-cluster',
                    html: `<span>${group.getChildCount()}</span>`,
                    iconSize: L.point(42, 42),
                }),
        });

        tileLayer.on('tileerror', () => setTilesFailed(true));
        zoom.addTo(map);
        tileLayer.addTo(map);
        cluster.addTo(map);
        mapRef.current = map;
        clusterRef.current = cluster;

        const resizeObserver = new ResizeObserver(() => map.invalidateSize());
        const markers = markerRef.current;
        resizeObserver.observe(containerRef.current);

        return () => {
            resizeObserver.disconnect();
            map.remove();
            mapRef.current = null;
            clusterRef.current = null;
            markers.clear();
        };
    }, [
        config.attribution,
        config.default_center,
        config.default_zoom,
        config.tile_url,
        direction,
    ]);

    useEffect(() => {
        const cluster = clusterRef.current;
        const map = mapRef.current;

        if (!cluster || !map) {
            return;
        }

        cluster.clearLayers();
        markerRef.current.clear();

        assets.forEach((asset) => {
            const latitude = asset.latitude;
            const longitude = asset.longitude;

            if (
                latitude === null ||
                latitude === undefined ||
                longitude === null ||
                longitude === undefined
            ) {
                return;
            }

            const marker = L.marker([latitude, longitude], {
                title: asset.title,
                alt: asset.title,
                keyboard: true,
                icon: L.divIcon({
                    className: 'pmc-map-marker-shell',
                    html: '<span data-testid="property-map-marker"><i class="bi bi-building"></i></span>',
                    iconAnchor: [18, 36],
                    iconSize: [36, 36],
                }),
                riseOnHover: true,
            });
            const elementClass = `is-${safeStatus(asset.occupancy_status)}${asset.id === selectedAssetId ? ' is-selected' : ''}`;

            marker.on('add', () => {
                marker.getElement()?.classList.add(...elementClass.split(' '));
            });
            marker.on('click', () => onSelectRef.current(asset));
            cluster.addLayer(marker);
            markerRef.current.set(asset.id, marker);
        });

        if (assets.length > 0 && fitRequest === 0) {
            fitMap(map, assets, config);
        }
    }, [assets, config, fitRequest, selectedAssetId]);

    useEffect(() => {
        const map = mapRef.current;
        const marker = selectedAssetId
            ? markerRef.current.get(selectedAssetId)
            : null;

        if (!map || !marker) {
            return;
        }

        const position = marker.getLatLng();
        map.flyTo(position, Math.max(map.getZoom(), 13), {
            duration: 0.45,
        });
    }, [selectedAssetId]);

    useEffect(() => {
        if (fitRequest === 0 || !mapRef.current) {
            return;
        }

        fitMap(mapRef.current, assets, config);
    }, [assets, config, fitRequest]);

    return (
        <div className="pmc-geographic-map-wrap">
            <div
                ref={containerRef}
                className="pmc-property-map-canvas"
                data-testid="property-map-canvas"
                aria-label={t('map.canvas_title')}
            />
            {tilesFailed ? (
                <div className="pmc-map-tile-warning" role="status">
                    <i className="bi bi-wifi-off" />
                    {t('map.tiles_failed')}
                </div>
            ) : null}
        </div>
    );
}

function fitMap(
    map: L.Map,
    assets: PropertyMapAsset[],
    config: PropertyMapConfig,
) {
    const points = assets
        .filter(
            (
                asset,
            ): asset is PropertyMapAsset & {
                latitude: number;
                longitude: number;
            } => asset.latitude !== null && asset.longitude !== null,
        )
        .map((asset) => L.latLng(asset.latitude, asset.longitude));

    if (points.length === 0) {
        map.setView(config.default_center, config.default_zoom);

        return;
    }

    if (points.length === 1) {
        map.setView(points[0], 13);

        return;
    }

    map.fitBounds(L.latLngBounds(points), {
        padding: [36, 36],
        maxZoom: 14,
    });
}

function MapMetric({
    icon,
    label,
    value,
    detail,
    tone,
}: {
    icon: string;
    label: string;
    value: string | number;
    detail: string;
    tone?: 'teal';
}) {
    return (
        <article
            className={`pmc-property-map-metric ${tone ? `is-${tone}` : ''}`}
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

function PropertyMapDetail({ asset }: { asset: PropertyMapAsset | null }) {
    const { locale, t } = useTranslator();

    if (!asset) {
        return (
            <aside
                className="pmc-property-map-detail"
                data-testid="property-map-detail"
            >
                <div className="pmc-property-map-detail-empty">
                    <span>
                        <i className="bi bi-cursor" />
                    </span>
                    <strong>{t('map.no_selection')}</strong>
                    <p>{t('map.no_selection_description')}</p>
                </div>
            </aside>
        );
    }

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
                        {asset.code} ·{' '}
                        {asset.land_number ?? t('map.no_land_number')}
                    </p>
                </div>
                <em className={asset.map_ready ? 'is-ready' : 'is-setup'}>
                    {asset.map_ready ? t('map.ready') : t('map.needs_setup')}
                </em>
            </header>

            {asset.is_showcase ? (
                <span className="pmc-showcase-badge">{t('map.showcase')}</span>
            ) : null}

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
                        {asset.latitude !== null && asset.longitude !== null
                            ? `${asset.latitude}, ${asset.longitude}`
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

function uniqueValues(
    values: Array<string | null | undefined>,
    locale: string,
): string[] {
    return Array.from(
        new Set(
            values.filter(
                (value): value is string =>
                    typeof value === 'string' && value.trim() !== '',
            ),
        ),
    ).sort((first, second) => first.localeCompare(second, locale));
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

function safeStatus(status: string): string {
    return status.replace(/[^a-z0-9_-]/gi, '').toLowerCase() || 'unknown';
}
