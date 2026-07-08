export function currency(
    value: number,
    locale = 'en',
    currencyCode = 'SAR',
): string {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currencyCode,
        maximumFractionDigits: 0,
    }).format(value || 0);
}

export function humanDate(
    value: string | null | undefined,
    locale = 'en',
): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat(locale, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
}

export function percent(value: number): string {
    return `${Number.isFinite(value) ? value.toFixed(1) : '0.0'}%`;
}
