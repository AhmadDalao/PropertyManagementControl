import type { PaginatedData, SharedProps } from '@/types';

export type WordingEntry = {
    group: string;
    key: string;
    english: string;
    arabic: string;
    default_english: string;
    default_arabic: string;
    customized: boolean;
};

export type MissingContent = {
    module: string;
    title: string;
    subtitle: string;
    missing: string;
    href: string;
};

export type WordingFilters = {
    search: string;
    group: string;
    state: string;
    perPage: number;
    contentModule: string;
};

export type ContentTranslations = {
    items: MissingContent[];
    total: number;
    counts: Record<string, number>;
    modules: string[];
};

export type WordingPageProps = SharedProps & {
    entries: PaginatedData<WordingEntry>;
    groups: string[];
    customizedCount: number;
    totalLabels: number;
    filters: WordingFilters;
    contentTranslations: ContentTranslations;
};

export type WordingTab = 'wording' | 'content';
export type WordingFilterOverrides = Record<string, string | number>;
export type WordingGroupLabel = (group: string) => string;
