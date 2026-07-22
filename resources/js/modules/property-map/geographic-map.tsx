import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';

import { useTranslator } from '@/lib/i18n';

import type { PropertyMapAsset, PropertyMapConfig } from './types';
import { useGeographicMap } from './use-geographic-map';

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
    const { containerRef, tilesFailed } = useGeographicMap({
        assets,
        selectedAssetId,
        config,
        direction,
        fitRequest,
        onSelect,
    });

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
