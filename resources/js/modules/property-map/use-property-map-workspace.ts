import { useState } from 'react';

import { filterMapAssets, uniqueValues } from './map-utils';
import type { PropertyMapAsset, PropertyMapConfig } from './types';

export function usePropertyMapWorkspace(
    assets: PropertyMapAsset[],
    config: PropertyMapConfig,
    locale: string,
) {
    const [selectedAssetId, setSelectedAssetId] = useState<number | null>(null);
    const [search, setSearchState] = useState('');
    const [zone, setZoneState] = useState('all');
    const [occupancy, setOccupancyState] = useState('all');
    const [page, setPage] = useState(1);
    const [fitRequest, setFitRequest] = useState(0);
    const visibleAssets = filterMapAssets(assets, {
        search,
        zone,
        occupancy,
        locale,
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
    const selectedAsset =
        visibleAssets.find((asset) => asset.id === selectedAssetId) ??
        positionedAssets[0] ??
        visibleAssets[0] ??
        null;

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
        setSearchState('');
        setZoneState('all');
        setOccupancyState('all');
        setPage(1);
    };

    return {
        fitRequest,
        hasFilters:
            search.trim() !== '' || zone !== 'all' || occupancy !== 'all',
        lastPage,
        nextPage: () => setPage((value) => Math.min(lastPage, value + 1)),
        occupancy,
        occupancyStates: uniqueValues(
            assets.map((asset) => asset.occupancy_status),
            locale,
        ),
        pageAssets,
        positionedAssets,
        previousPage: () => setPage((value) => Math.max(1, value - 1)),
        requestFit: () => setFitRequest((value) => value + 1),
        resetFilters,
        safePage,
        search,
        selectAsset,
        selectedAsset,
        selectedAssetId,
        setOccupancy: (value: string) => {
            setOccupancyState(value);
            setPage(1);
        },
        setSearch: (value: string) => {
            setSearchState(value);
            setPage(1);
        },
        setZone: (value: string) => {
            setZoneState(value);
            setPage(1);
        },
        setupAssets: assets.filter((asset) => !asset.map_ready),
        totalValue: assets.reduce(
            (sum, asset) => sum + Number(asset.valuation_amount ?? 0),
            0,
        ),
        visibleAssets,
        zone,
        zones: uniqueValues(
            assets.map((asset) => asset.zone),
            locale,
        ),
    };
}
