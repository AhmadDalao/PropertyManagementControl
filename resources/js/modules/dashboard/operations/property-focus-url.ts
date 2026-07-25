export function propertyFocusUrl(
    href: string,
    propertyId?: number | null,
): string {
    if (!propertyId) {
        return href;
    }

    const separator = href.includes('?') ? '&' : '?';

    return `${href}${separator}property_id=${propertyId}`;
}
