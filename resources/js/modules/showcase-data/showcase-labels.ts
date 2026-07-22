import type { Translator } from '@/lib/i18n';

export const primaryCountKeys = [
    'buildings',
    'units',
    'tenants',
    'leases',
    'payments',
    'documents',
] as const;

export const headlineTargetKeys = [
    'buildings',
    'units',
    'tenants',
    'documents',
] as const;

export function showcaseLabel(key: string, t: Translator): string {
    return t(`showcase.${key}`, key.replaceAll('_', ' '));
}
