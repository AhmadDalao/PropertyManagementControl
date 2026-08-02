import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
    PropertyOption,
} from '@/types';

export type DocumentRecord = {
    id: number;
    type: string;
    title_en: string;
    title_ar?: string | null;
    original_name: string;
    file_size: number;
    is_public: boolean;
    issued_on?: string | null;
    expires_on?: string | null;
    expiry_status: 'expired' | 'due_30' | 'due_90' | 'current' | 'no_expiry';
    expiry_days?: number | null;
    created_at?: string | null;
    download_url: string;
    attachment: {
        type: string;
        label: string;
        url?: string | null;
    };
    portfolio: { name_en?: string | null; name_ar?: string | null };
    uploaded_by: { name?: string | null };
};

export type DocumentInsights = {
    total: number;
    contracts: number;
    signed: number;
    receipts: number;
    portal_visible: number;
    expired: number;
    expiring_90: number;
    no_expiry: number;
};

export type DocumentIndexPageProps = SharedProps & {
    documents: PaginatedData<DocumentRecord>;
    documentInsights: DocumentInsights;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: PropertyOption[];
    typeOptions: string[];
    attachmentOptions: string[];
    visibilityOptions: string[];
    expiryOptions: string[];
};
