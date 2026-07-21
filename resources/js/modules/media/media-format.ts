import type { MediaRecord } from './types';

export function formatMediaBytes(value: number, locale: 'en' | 'ar'): string {
    if (!value) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(
        Math.floor(Math.log(value) / Math.log(1024)),
        units.length - 1,
    );
    const number = value / 1024 ** index;

    return `${new Intl.NumberFormat(locale === 'ar' ? 'ar-SA' : 'en', {
        maximumFractionDigits: index === 0 ? 0 : 1,
    }).format(number)} ${units[index]}`;
}

export function formatMediaDimensions(media: MediaRecord): string {
    return media.width && media.height
        ? `${media.width} x ${media.height} px`
        : '-';
}

export function localizedMediaTitle(
    media: Pick<MediaRecord, 'title_en' | 'title_ar'>,
    locale: 'en' | 'ar',
): string {
    return (
        (locale === 'ar'
            ? media.title_ar || media.title_en
            : media.title_en || media.title_ar) ?? ''
    );
}

export function localizedMediaAlt(
    media: Pick<
        MediaRecord,
        'alt_text_en' | 'alt_text_ar' | 'title_en' | 'title_ar'
    >,
    locale: 'en' | 'ar',
): string {
    return (
        (locale === 'ar'
            ? media.alt_text_ar || media.alt_text_en
            : media.alt_text_en || media.alt_text_ar) ||
        localizedMediaTitle(media, locale)
    );
}
