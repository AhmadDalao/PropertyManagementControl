import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type MediaRecord = {
    id: number;
    title_en?: string | null;
    title_ar?: string | null;
    alt_text_en?: string | null;
    alt_text_ar?: string | null;
    filename: string;
    collection: string;
    visibility: string;
    mime_type?: string | null;
    size: number;
    width?: number | null;
    height?: number | null;
    file_url: string;
    created_at?: string | null;
    portfolio?: { name_en?: string | null; name_ar?: string | null };
    uploaded_by?: { name?: string | null };
};

export type MediaInsights = {
    total: number;
    public: number;
    private: number;
    bytes: number;
    collections: number;
};

export type MediaPickerOption = {
    id: number;
    title_en?: string | null;
    title_ar?: string | null;
    alt_text_en?: string | null;
    alt_text_ar?: string | null;
    url: string;
    width?: number | null;
    height?: number | null;
};

export type MediaIndexPageProps = SharedProps & {
    mediaFiles: PaginatedData<MediaRecord>;
    mediaInsights: MediaInsights;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    collectionOptions: string[];
};
