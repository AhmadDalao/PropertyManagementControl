import type {
    PaginatedData,
    PropertyOption,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type RentRollState = 'occupied' | 'vacant' | 'arrears' | 'expiring';

export type RentRollLease = {
    id: number;
    code: string;
    tenant: string;
    status: string;
    payment_frequency: string;
    started_at: string | null;
    ends_at: string | null;
    days_remaining: number | null;
    rent_amount: number;
    deposit_amount: number;
    currency: string;
    total_due: number;
    total_paid: number;
    balance: number;
    overdue: number;
};

export type RentRollRecord = {
    id: number;
    code: string;
    title_en: string;
    title_ar: string;
    asset_type: string;
    usage_type: string;
    state: RentRollState;
    hierarchy: Array<{
        id: number;
        title_en: string;
        title_ar: string;
        code: string;
    }>;
    property: {
        id: number;
        code: string;
        title_en: string;
        title_ar: string;
    } | null;
    portfolio: {
        id: number;
        name: string | null;
    };
    lease: RentRollLease | null;
    links: {
        asset: string;
        lease: string | null;
    };
    is_showcase: boolean;
};

export type RentRollCurrencyPosition = {
    currency: string;
    active_leases: number;
    contracted: number;
    paid: number;
    outstanding: number;
    overdue: number;
    deposits: number;
};

export type RentRollPageProps = SharedProps & {
    records: PaginatedData<RentRollRecord>;
    filters: TableFilters & {
        search: string;
        state: string;
        portfolio_id: number | null;
        property_id: number | null;
        per_page: number;
    };
    counts: TableCount[];
    insights: {
        matching: number;
        occupied: number;
        vacant: number;
        attention: number;
    };
    currencyPositions: RentRollCurrencyPosition[];
    scope: Array<{ label: string; value: string }>;
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: PropertyOption[];
    stateOptions: string[];
    downloads: {
        pdf: string;
        docx: string;
        xlsx: string;
    };
};
