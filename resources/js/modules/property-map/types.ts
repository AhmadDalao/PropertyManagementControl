export type PropertyMapAsset = {
    id: number;
    title: string;
    code: string;
    portfolio?: string | null;
    asset_type: string;
    usage_type: string;
    status: string;
    occupancy_status: string;
    valuation_amount: number;
    currency: string;
    address?: string | null;
    zone?: string | null;
    land_number?: string | null;
    latitude?: number | null;
    longitude?: number | null;
    x: number;
    y: number;
    has_coordinates: boolean;
    has_identity: boolean;
    map_ready: boolean;
    href: string;
    edit_href: string;
    children_count: number;
    rentable_children_count: number;
    active_leases_count: number;
    open_requests_count: number;
    owner?: string | null;
    manager?: string | null;
    is_showcase: boolean;
};

export type PropertyMapSummary = {
    mapped: number;
    total: number;
    ready: number;
    needs_position: number;
    needs_identity: number;
    coverage_percent: number;
    zones: string[];
    payload_limit: number;
};

export type PropertyMapConfig = {
    tile_url: string;
    attribution: string;
    default_center: [number, number];
    default_zoom: number;
    directory_page_size: number;
};

export type PropertyMapPayload = {
    assets: PropertyMapAsset[];
    summary: PropertyMapSummary;
    config: PropertyMapConfig;
};
