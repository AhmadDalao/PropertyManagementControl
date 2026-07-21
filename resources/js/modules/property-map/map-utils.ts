import type { Translator } from '@/lib/i18n';

import type { PropertyMapAsset } from './types';

export function filterMapAssets(
    assets: PropertyMapAsset[],
    {
        search,
        zone,
        occupancy,
        locale,
    }: {
        search: string;
        zone: string;
        occupancy: string;
        locale: string;
    },
): PropertyMapAsset[] {
    const normalizedSearch = search.trim().toLocaleLowerCase(locale);

    return assets.filter((asset) => {
        const matchesZone = zone === 'all' || asset.zone === zone;
        const matchesOccupancy =
            occupancy === 'all' || asset.occupancy_status === occupancy;
        const matchesSearch =
            normalizedSearch === '' ||
            [
                asset.title,
                asset.code,
                asset.portfolio,
                asset.zone,
                asset.land_number,
                asset.address,
                asset.owner,
                asset.manager,
            ].some((value) =>
                String(value ?? '')
                    .toLocaleLowerCase(locale)
                    .includes(normalizedSearch),
            );

        return matchesZone && matchesOccupancy && matchesSearch;
    });
}

export function uniqueValues(
    values: Array<string | null | undefined>,
    locale: string,
): string[] {
    return Array.from(
        new Set(
            values.filter(
                (value): value is string =>
                    typeof value === 'string' && value.trim() !== '',
            ),
        ),
    ).sort((first, second) => first.localeCompare(second, locale));
}

export function statusLabel(status: string, t: Translator): string {
    return t(
        `status.${status}`,
        status
            .replaceAll('_', ' ')
            .replace(/\b\w/g, (letter) => letter.toUpperCase()),
    );
}

export function safeStatus(status: string): string {
    return status.replace(/[^a-z0-9_-]/gi, '').toLowerCase() || 'unknown';
}
