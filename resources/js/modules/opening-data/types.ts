import type { SharedProps } from '@/types';

export type OpeningDataPortfolio = {
    id: number;
    name: string;
    code: string;
};

export type OpeningDataIssue = {
    sheet: string;
    row: number | null;
    field: string | null;
    message: string;
};

export type OpeningDataPreview = {
    token: string;
    portfolio: {
        id: number;
        code: string;
        name_en: string;
        name_ar: string;
    };
    expires_at: string;
    ready: boolean;
    counts: Record<string, number>;
    issue_count: number;
    issues: OpeningDataIssue[];
    issues_truncated: boolean;
    samples: Record<string, Array<Record<string, unknown>>>;
};

export type OpeningDataPayload = {
    portfolios: OpeningDataPortfolio[];
    preview: OpeningDataPreview | null;
    limits: Record<string, number>;
    maxFileMegabytes: number;
};

export type OpeningDataPageProps = SharedProps & {
    openingData: OpeningDataPayload;
};
