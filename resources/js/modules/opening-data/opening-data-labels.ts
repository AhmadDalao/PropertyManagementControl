import type { Translator } from '@/lib/i18n';

export const openingDataSheetOrder = [
    'Assets',
    'Tenants',
    'Leases',
    'Payments',
] as const;

export function sheetLabel(sheet: string, t: Translator): string {
    const keys: Record<string, Parameters<typeof t>[0]> = {
        Assets: 'opening_data.assets',
        Tenants: 'opening_data.tenants',
        Leases: 'opening_data.leases',
        Payments: 'opening_data.payments',
    };

    return t(keys[sheet] ?? 'opening_data.preview');
}

export function fieldLabel(field: string | null, t: Translator): string {
    return field
        ? t(`opening_data.columns.${field}`)
        : t('opening_data.preview');
}
