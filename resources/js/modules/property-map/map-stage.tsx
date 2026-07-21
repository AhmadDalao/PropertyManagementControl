import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import { GeographicMap } from './geographic-map';
import type { PropertyMapAsset, PropertyMapConfig } from './types';

type PropertyMapStageProps = {
    assets: PropertyMapAsset[];
    allAssets: PropertyMapAsset[];
    selectedAssetId: number | null;
    config: PropertyMapConfig;
    direction: 'ltr' | 'rtl';
    fitRequest: number;
    onSelect: (asset: PropertyMapAsset) => void;
    onFit: () => void;
};

export function PropertyMapStage({
    assets,
    allAssets,
    selectedAssetId,
    config,
    direction,
    fitRequest,
    onSelect,
    onFit,
}: PropertyMapStageProps) {
    const { t } = useTranslator();

    return (
        <div className="pmc-property-map-stage">
            <header>
                <div>
                    <span>{t('map.map_provider')}</span>
                    <strong>{t('map.canvas_title')}</strong>
                </div>
                <button
                    type="button"
                    className="btn btn-sm btn-outline-secondary"
                    onClick={onFit}
                    disabled={assets.length === 0}
                >
                    <i className="bi bi-arrows-fullscreen" />
                    {t('map.fit_results')}
                </button>
            </header>
            <GeographicMap
                assets={assets}
                selectedAssetId={selectedAssetId}
                config={config}
                direction={direction}
                fitRequest={fitRequest}
                onSelect={onSelect}
            />
            {assets.length === 0 ? (
                <div className="pmc-property-map-empty">
                    <span>
                        <i className="bi bi-geo-alt" />
                    </span>
                    <strong>{t('map.empty_title')}</strong>
                    <p>{t('map.empty_description')}</p>
                    {allAssets[0] ? (
                        <Link
                            href={allAssets[0].edit_href}
                            className="btn btn-primary"
                        >
                            {t('map.add_first_position')}
                        </Link>
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}
