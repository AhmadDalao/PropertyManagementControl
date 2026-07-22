import type { CmsContent, CmsItem, PublicPageRecord } from './types';

export function contentText(content: CmsContent, key: string, fallback = '') {
    const value = content[key];

    return typeof value === 'string' ? value : fallback;
}

export function contentItems(content: CmsContent, key: string) {
    const value = content[key];

    return Array.isArray(value) ? (value as CmsItem[]) : [];
}

export function contentIcon(item: CmsItem, fallback = 'bi-grid') {
    const icon = item.icon;

    return typeof icon === 'string' && icon.startsWith('bi-') ? icon : fallback;
}

export function localizedPageField(
    page: PublicPageRecord,
    field: 'title' | 'seo_title' | 'seo_description',
    locale: 'en' | 'ar',
) {
    const preferred = page[`${field}_${locale}`];
    const fallback = page[`${field}_${locale === 'ar' ? 'en' : 'ar'}`];

    return preferred || fallback || '';
}
