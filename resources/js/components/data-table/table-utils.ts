import { replaceTokens } from '@/lib/i18n';
import type { TableFilters } from '@/types';

import type { TableFilterField } from './types';

export const PAGE_SIZES = [10, 25, 50, 100];

export const IGNORED_ACTIVE_FILTERS = new Set([
    'page',
    'per_page',
    'sort',
    'direction',
    'search',
]);

export function exportUrl(path: string, filters: TableFilters): string {
    const query = new URLSearchParams(
        cleanFilters(stringifyFilters(filters)),
    ).toString();

    return query ? `${path}?${query}` : path;
}

export function filterLabel(name: string, fields: TableFilterField[]): string {
    return (
        fields.find((field) => field.name === name)?.label ??
        name.replaceAll('_', ' ')
    );
}

export function interpolate(
    value: string,
    replacements: Record<string, string | number>,
): string {
    return replaceTokens(value, replacements);
}

export function stringifyFilters(
    filters: TableFilters,
): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).map(([key, value]) => [
            key,
            value === null || value === undefined ? '' : String(value),
        ]),
    );
}

export function cleanFilters(
    filters: Record<string, string>,
): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([key, value]) =>
                value !== '' &&
                value !== 'all' &&
                !(key === 'page' && value === '1'),
        ),
    );
}

export function cleanPaginationLabel(label: string): string {
    const cleanLabel = label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .trim();

    return cleanLabel || label;
}

export function isShowcaseRow(row: unknown): boolean {
    return Boolean(
        typeof row === 'object' &&
        row !== null &&
        'is_showcase' in row &&
        row.is_showcase,
    );
}
