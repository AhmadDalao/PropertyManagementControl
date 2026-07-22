import type L from 'leaflet';
import { useEffect, useRef, useState } from 'react';

import {
    fitPropertyMap,
    focusPropertyMarker,
    initializePropertyMap,
    renderPropertyMarkers,
} from './map-leaflet';
import type { PropertyMapRuntime } from './map-leaflet';
import type { PropertyMapAsset, PropertyMapConfig } from './types';

type GeographicMapOptions = {
    assets: PropertyMapAsset[];
    selectedAssetId: number | null;
    config: PropertyMapConfig;
    direction: 'ltr' | 'rtl';
    fitRequest: number;
    onSelect: (asset: PropertyMapAsset) => void;
};

export function useGeographicMap({
    assets,
    selectedAssetId,
    config,
    direction,
    fitRequest,
    onSelect,
}: GeographicMapOptions) {
    const containerRef = useRef<HTMLDivElement | null>(null);
    const runtimeRef = useRef<PropertyMapRuntime | null>(null);
    const markersRef = useRef(new Map<number, L.Marker>());
    const onSelectRef = useRef(onSelect);
    const [tilesFailed, setTilesFailed] = useState(false);
    const {
        attribution,
        default_center: defaultCenter,
        default_zoom: defaultZoom,
        tile_url: tileUrl,
    } = config;

    useEffect(() => {
        onSelectRef.current = onSelect;
    }, [onSelect]);

    useEffect(() => {
        const container = containerRef.current;

        if (!container || runtimeRef.current) {
            return;
        }

        const runtime = initializePropertyMap(
            container,
            {
                attribution,
                default_center: defaultCenter,
                default_zoom: defaultZoom,
                tile_url: tileUrl,
            },
            direction,
            () => setTilesFailed(true),
        );
        const markers = markersRef.current;
        const resizeObserver = new ResizeObserver(() =>
            runtime.map.invalidateSize(),
        );

        runtimeRef.current = runtime;
        resizeObserver.observe(container);

        return () => {
            resizeObserver.disconnect();
            runtime.map.remove();
            runtimeRef.current = null;
            markers.clear();
        };
    }, [attribution, defaultCenter, defaultZoom, tileUrl, direction]);

    useEffect(() => {
        const runtime = runtimeRef.current;

        if (!runtime) {
            return;
        }

        renderPropertyMarkers(
            runtime,
            assets,
            selectedAssetId,
            markersRef.current,
            (asset) => onSelectRef.current(asset),
        );

        if (assets.length > 0 && fitRequest === 0) {
            fitPropertyMap(runtime.map, assets, config);
        }
    }, [assets, config, fitRequest, selectedAssetId]);

    useEffect(() => {
        const map = runtimeRef.current?.map;
        const marker = selectedAssetId
            ? markersRef.current.get(selectedAssetId)
            : null;

        if (map && marker) {
            focusPropertyMarker(map, marker);
        }
    }, [selectedAssetId]);

    useEffect(() => {
        const map = runtimeRef.current?.map;

        if (fitRequest > 0 && map) {
            fitPropertyMap(map, assets, config);
        }
    }, [assets, config, fitRequest]);

    return { containerRef, tilesFailed };
}
