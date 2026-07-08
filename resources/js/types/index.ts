export type * from './auth';

export type TranslationMap = Record<string, unknown>;

export type SharedProps = {
    name: string;
    url: string;
    auth: {
        user: import('./auth').AppUser | null;
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
    location: string;
    sort_order: number;
    children?: NavigationItemRecord[];
};

export type OptionRecord = {
    id: number;
    name?: string;
    title_en?: string;
    title_ar?: string;
    [key: string]: unknown;
};
