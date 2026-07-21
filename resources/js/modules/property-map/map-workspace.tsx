import { useTranslator } from '@/lib/i18n';

import { PropertyMapFilters } from './map-filters';
import { PropertyMapMetrics } from './map-metrics';
import { PropertyMapSetupStatus } from './map-setup-status';
import { PropertyMapStage } from './map-stage';
import { PropertyMapDetail } from './property-map-detail';
import { PropertyMapDirectory } from './property-map-directory';
import type { PropertyMapWorkspaceProps } from './types';
import { usePropertyMapWorkspace } from './use-property-map-workspace';

export function PropertyMapWorkspace({
    assets,
    summary,
    config,
    toolbar,
}: PropertyMapWorkspaceProps) {
    const { direction, locale, t } = useTranslator();
    const workspace = usePropertyMapWorkspace(assets, config, locale);

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

            <PropertyMapMetrics
                summary={summary}
                totalValue={workspace.totalValue}
            />
            <PropertyMapFilters
                search={workspace.search}
                zone={workspace.zone}
                occupancy={workspace.occupancy}
                zones={workspace.zones}
                occupancyStates={workspace.occupancyStates}
                visibleCount={workspace.visibleAssets.length}
                totalCount={assets.length}
                hasFilters={workspace.hasFilters}
                onSearch={workspace.setSearch}
                onZone={workspace.setZone}
                onOccupancy={workspace.setOccupancy}
                onReset={workspace.resetFilters}
            />
            <PropertyMapSetupStatus
                assets={assets}
                setupAssets={workspace.setupAssets}
            />

            <div className="pmc-property-map-layout">
                <PropertyMapStage
                    assets={workspace.positionedAssets}
                    allAssets={assets}
                    selectedAssetId={workspace.selectedAssetId}
                    config={config}
                    direction={direction}
                    fitRequest={workspace.fitRequest}
                    onSelect={workspace.selectAsset}
                    onFit={workspace.requestFit}
                />
                <PropertyMapDetail asset={workspace.selectedAsset} />
            </div>

            <PropertyMapDirectory
                assets={workspace.pageAssets}
                visibleCount={workspace.visibleAssets.length}
                selectedAsset={workspace.selectedAsset}
                safePage={workspace.safePage}
                lastPage={workspace.lastPage}
                onSelect={workspace.selectAsset}
                onPrevious={workspace.previousPage}
                onNext={workspace.nextPage}
                onReset={workspace.resetFilters}
            />
        </section>
    );
}
