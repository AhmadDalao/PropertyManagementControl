import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type AssetStakeholder = {
    relationship_type: string;
    user?: { id: number; name: string } | null;
};

export type AssetRecord = {
    id: number;
    parent_id?: number | null;
    asset_type: string;
    usage_type: string;
    title_en: string;
    title_ar: string;
    code: string;
    status: string;
    occupancy_status: string;
    rentable: boolean;
    valuation_amount: number;
    currency: string;
    area?: number | null;
    level_label?: string | null;
    unit_label?: string | null;
    stakeholders?: AssetStakeholder[];
    parent?: { title_en: string; title_ar?: string | null } | null;
    children_count?: number;
    active_leases_count?: number;
};

export type AssetInsights = {
    total_assets: number;
    total_value: number;
    vacant_rentable_assets: number;
    occupied_assets: number;
    buildings: number;
    units: number;
    missing_owner: number;
    missing_manager: number;
    rentable_occupancy_rate: number;
};

export type AssetIndexPageProps = SharedProps & {
    assets: PaginatedData<AssetRecord>;
    filters: TableFilters;
    counts: TableCount[];
    insights: AssetInsights;
    portfolioOptions: Array<{ id: number; name: string }>;
};
