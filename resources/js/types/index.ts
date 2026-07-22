import type { AppUser } from './auth';

export type * from './auth';

export type TranslationMap = Record<string, unknown>;

export type LocalizedCopy = {
    key: string;
    fallback?: string;
    replacements?: Record<string, string | number>;
};

export type SharedProps = {
    name: string;
    url: string;
    auth: {
        user: AppUser | null;
    };
    app: {
        name: string;
        locale: 'en' | 'ar';
        direction: 'ltr' | 'rtl';
        translations: TranslationMap;
    };
    flash: {
        success?: string | null;
        error?: string | null;
        warning?: string | null;
        status?: string | null;
    };
    publicNavigation: {
        header: NavigationItemRecord[];
    };
};

export type NavigationItemRecord = {
    id: number;
    title_en: string;
    title_ar: string;
    url?: string | null;
    target?: string;
    location: string;
    sort_order: number;
    page?: {
        slug: string;
        title_en: string;
        title_ar: string;
        is_homepage: boolean;
    } | null;
    children?: NavigationItemRecord[];
};

export type OptionRecord = {
    id: number;
    name?: string;
    title_en?: string;
    title_ar?: string;
    [key: string]: unknown;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedData<T> = {
    data: T[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    path: string;
    per_page: number;
    to: number | null;
    total: number;
};

export type TableFilters = Record<
    string,
    string | number | boolean | null | undefined
>;

export type TableCount = {
    label: string;
    value: number;
    filter?: Record<string, string | number | null>;
    active?: boolean;
};
