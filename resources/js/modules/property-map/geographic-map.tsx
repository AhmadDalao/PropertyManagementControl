import L from 'leaflet';
import 'leaflet.markercluster';
import { useEffect, useRef, useState } from 'react';

import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';

import { useTranslator } from '@/lib/i18n';

import { safeStatus } from './map-utils';
import type { PropertyMapAsset, PropertyMapConfig } from './types';

type GeographicMapProps = {
    assets: PropertyMapAsset[];
    selectedAssetId: number | null;
    config: PropertyMapConfig;
    direction: 'ltr' | 'rtl';
    fitRequest: number;
    onSelect: (asset: PropertyMapAsset) => void;
};

export function GeographicMap({
    assets,
    selectedAssetId,
    config,
    direction,
    fitRequest,
    onSelect,
}: GeographicMapProps) {
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
            iconCreateFunction: clusterIcon,
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

            const marker = createMarker(asset, latitude, longitude);
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

function createMarker(
    asset: PropertyMapAsset,
    latitude: number,
    longitude: number,
): L.Marker {
    return L.marker([latitude, longitude], {
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
}

function clusterIcon(group: L.MarkerCluster): L.DivIcon {
    return L.divIcon({
        className: 'pmc-map-cluster',
        html: `<span>${group.getChildCount()}</span>`,
        iconSize: L.point(42, 42),
    });
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
