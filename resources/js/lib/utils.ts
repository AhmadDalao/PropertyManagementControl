export function currency(
    value: number,
    locale = 'en',
    currencyCode = 'SAR',
): string {
    return new Intl.NumberFormat(locale === 'ar' ? 'ar-SA' : 'en', {
        style: 'currency',
        currency: currencyCode,
        maximumFractionDigits: 0,
    }).format(value || 0);
}

export function compactCurrency(
    value: number,
    locale = 'en',
    currencyCode = 'SAR',
): string {
    return new Intl.NumberFormat(localeTag(locale), {
        style: 'currency',
        currency: currencyCode,
        notation: 'compact',
        compactDisplay: 'short',
        maximumFractionDigits: 1,
    }).format(value || 0);
}

function localeTag(locale: string): string {
    return locale === 'ar' ? 'ar-SA' : 'en';
}

export function humanDate(
    value: string | null | undefined,
    locale = 'en',
): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat(localeTag(locale), {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
}

export function dateTime(
    value: string | null | undefined,
    locale = 'en',
): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat(localeTag(locale), {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

export function percent(value: number, locale = 'en'): string {
    return new Intl.NumberFormat(localeTag(locale), {
        style: 'percent',
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    }).format((Number.isFinite(value) ? value : 0) / 100);
}

export function localizedNumber(
    value: number,
    locale = 'en',
    minimumIntegerDigits = 1,
): string {
    return new Intl.NumberFormat(localeTag(locale), {
        minimumIntegerDigits,
        maximumFractionDigits: 1,
    }).format(Number.isFinite(value) ? value : 0);
}
