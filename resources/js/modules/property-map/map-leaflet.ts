import L from 'leaflet';
import 'leaflet.markercluster';

import { safeStatus } from './map-utils';
import type { PropertyMapAsset, PropertyMapConfig } from './types';

export type PropertyMapRuntime = {
    map: L.Map;
    cluster: L.MarkerClusterGroup;
};

type PropertyMapInitializationConfig = Pick<
    PropertyMapConfig,
    'attribution' | 'default_center' | 'default_zoom' | 'tile_url'
>;

export function initializePropertyMap(
    container: HTMLDivElement,
    config: PropertyMapInitializationConfig,
    direction: 'ltr' | 'rtl',
    onTileError: () => void,
): PropertyMapRuntime {
    const map = L.map(container, {
        center: config.default_center,
        zoom: config.default_zoom,
        zoomControl: false,
        preferCanvas: true,
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
        iconCreateFunction: createClusterIcon,
    });

    tileLayer.on('tileerror', onTileError);
    L.control
        .zoom({ position: direction === 'rtl' ? 'topright' : 'topleft' })
        .addTo(map);
    tileLayer.addTo(map);
    cluster.addTo(map);

    return { map, cluster };
}

export function renderPropertyMarkers(
    runtime: PropertyMapRuntime,
    assets: PropertyMapAsset[],
    selectedAssetId: number | null,
    markers: Map<number, L.Marker>,
    onSelect: (asset: PropertyMapAsset) => void,
) {
    runtime.cluster.clearLayers();
    markers.clear();

    assets.forEach((asset) => {
        if (asset.latitude == null || asset.longitude == null) {
            return;
        }

        const marker = createPropertyMarker(
            asset,
            asset.latitude,
            asset.longitude,
        );
        const classes = [
            `is-${safeStatus(asset.occupancy_status)}`,
            asset.id === selectedAssetId ? 'is-selected' : '',
        ].filter(Boolean);

        marker.on('add', () => marker.getElement()?.classList.add(...classes));
        marker.on('click', () => onSelect(asset));
        runtime.cluster.addLayer(marker);
        markers.set(asset.id, marker);
    });
}

export function focusPropertyMarker(map: L.Map, marker: L.Marker) {
    map.flyTo(marker.getLatLng(), Math.max(map.getZoom(), 13), {
        duration: 0.45,
    });
}

export function fitPropertyMap(
    map: L.Map,
    assets: PropertyMapAsset[],
    config: PropertyMapConfig,
) {
    const points = positionedPoints(assets);

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

function createPropertyMarker(
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

function createClusterIcon(group: L.MarkerCluster): L.DivIcon {
    return L.divIcon({
        className: 'pmc-map-cluster',
        html: `<span>${group.getChildCount()}</span>`,
        iconSize: L.point(42, 42),
    });
}

function positionedPoints(assets: PropertyMapAsset[]): L.LatLng[] {
    return assets
        .filter(
            (
                asset,
            ): asset is PropertyMapAsset & {
                latitude: number;
                longitude: number;
            } => asset.latitude != null && asset.longitude != null,
        )
        .map((asset) => L.latLng(asset.latitude, asset.longitude));
}
