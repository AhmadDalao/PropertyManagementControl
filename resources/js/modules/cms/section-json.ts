import type { FormDataConvertible } from '@inertiajs/core';

export function parseJsonObject(
    value: string,
): Record<string, FormDataConvertible> | null {
    try {
        const parsed = JSON.parse(value || '{}') as unknown;

        return isPlainObject(parsed)
            ? (parsed as Record<string, FormDataConvertible>)
            : null;
    } catch {
        return null;
    }
}

export function safeJsonObject(value: string): Record<string, unknown> {
    try {
        const parsed = JSON.parse(value || '{}') as unknown;

        return isPlainObject(parsed) ? parsed : {};
    } catch {
        return {};
    }
}

export function isPlainObject(
    value: unknown,
): value is Record<string, unknown> {
    return value !== null && !Array.isArray(value) && typeof value === 'object';
}

export function readableSectionType(value: string) {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function jsonText(value: Record<string, unknown>) {
    return JSON.stringify(value, null, 2);
}

export function stringValue(value: unknown) {
    return typeof value === 'string' ? value : '';
}
