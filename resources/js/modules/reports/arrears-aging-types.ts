import type { RentCollectionRecord } from '@/modules/rent-collection/types';
import type {
    PaginatedData,
    PropertyOption,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type ArrearsAgingBucket =
    'days_1_30' | 'days_31_60' | 'days_61_90' | 'over_90';

export type ArrearsAgingRecord = RentCollectionRecord & {
    bucket: ArrearsAgingBucket;
    links: {
        follow_up: string;
        lease: string | null;
        asset: string | null;
    };
};

export type ArrearsCurrencyPosition = {
    currency: string;
    installment_count: number;
    lease_count: number;
    total: number;
    days_1_30: number;
    days_31_60: number;
    days_61_90: number;
    over_90: number;
};

export type ArrearsAgingPageProps = SharedProps & {
    records: PaginatedData<ArrearsAgingRecord>;
    filters: TableFilters & {
        search: string;
        bucket: string;
        portfolio_id: number | null;
        property_id: number | null;
        per_page: number;
    };
    counts: TableCount[];
    insights: {
        installments: number;
        leases: number;
        tenants: number;
        oldest_days: number;
    };
    currencyPositions: ArrearsCurrencyPosition[];
    scope: Array<{ label: string; value: string }>;
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: PropertyOption[];
    bucketOptions: string[];
    downloads: {
        pdf: string;
        docx: string;
        xlsx: string;
    };
};
