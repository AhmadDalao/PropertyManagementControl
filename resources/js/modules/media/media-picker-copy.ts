import type { MediaPickerOption } from './types';

export function mediaPickerTitle(
    option: MediaPickerOption,
    locale: string,
    fallback: string,
): string {
    return (
        (locale === 'ar'
            ? option.title_ar || option.title_en
            : option.title_en || option.title_ar) || fallback
    );
}

export function mediaPickerAlt(
    option: MediaPickerOption,
    locale: string,
    fallback: string,
): string {
    return (
        (locale === 'ar'
            ? option.alt_text_ar || option.alt_text_en
            : option.alt_text_en || option.alt_text_ar) ||
        mediaPickerTitle(option, locale, fallback)
    );
}
