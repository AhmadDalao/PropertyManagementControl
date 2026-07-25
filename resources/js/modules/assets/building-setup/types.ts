import type { SharedProps } from '@/types';

export type BuildingSetupOption = {
    value: string;
    label: string;
};

export type BuildingSetupValues = {
    portfolio_id: string;
    title_en: string;
    title_ar: string;
    code_prefix: string;
    usage_type: string;
    floor_count: number | string;
    units_per_floor: number | string;
    floor_start: number | string;
    unit_type: string;
    primary_owner_user_id: string;
    primary_manager_user_id: string;
    valuation_amount: number | string;
    currency: string;
    area: number | string;
    unit_area: number | string;
    address: string;
    address_ar: string;
    map_zone_en: string;
    map_zone_ar: string;
    land_number: string;
    latitude: number | string;
    longitude: number | string;
};

export type BuildingSetupPayload = {
    title: string;
    description: string;
    backHref: string;
    backLabel: string;
    action: string;
    submitLabel: string;
    isSetup: boolean;
    options: {
        portfolios: BuildingSetupOption[];
        usages: BuildingSetupOption[];
        unitTypes: BuildingSetupOption[];
        owners: BuildingSetupOption[];
        managers: BuildingSetupOption[];
    };
    initialValues: BuildingSetupValues;
    limits: {
        floors: number;
        unitsPerFloor: number;
        records: number;
    };
};

export type BuildingSetupPageProps = SharedProps & {
    buildingSetup: BuildingSetupPayload;
};

export type BuildingSetupFieldName = keyof BuildingSetupValues;

export type BuildingSetupErrors = Partial<
    Record<BuildingSetupFieldName, string>
>;

export type SetBuildingSetupValue = <Key extends BuildingSetupFieldName>(
    field: Key,
    value: BuildingSetupValues[Key],
) => void;
