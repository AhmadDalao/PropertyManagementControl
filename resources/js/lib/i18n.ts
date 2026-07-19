import { usePage } from '@inertiajs/react';

import type { SharedProps, TranslationMap } from '@/types';

export type UiTranslationKey =
    | `actions.${string}`
    | `common.${string}`
    | `dashboard.${string}`
    | `docs.${string}`
    | `nav.${string}`
    | `reports.${string}`
    | `roles.${string}`
    | `shell.${string}`
    | `status.${string}`
    | `table.${string}`;

export function useTranslator() {
    const { app } = usePage<SharedProps>().props;

    const t = (
        key: UiTranslationKey,
        fallback?: string,
        replacements: Record<string, string | number> = {},
    ): string => {
        const value = lookup(app.translations, key);
        const translated =
            typeof value === 'string' ? value : (fallback ?? key);

        return Object.entries(replacements).reduce(
            (copy, [name, replacement]) =>
                copy.replaceAll(`:${name}`, String(replacement)),
            translated,
        );
    };

    const text = (value: string): string => {
        const textTranslations = app.translations.text as
            TranslationMap | undefined;
        const translated =
            textTranslations !== undefined && value in textTranslations
                ? textTranslations[value]
                : undefined;

        return typeof translated === 'string' ? translated : value;
    };

    return {
        direction: app.direction,
        locale: app.locale,
        t,
        text,
    };
}

function lookup(source: TranslationMap, path: string): unknown {
    return path.split('.').reduce<unknown>((current, segment) => {
        if (
            typeof current !== 'object' ||
            current === null ||
            !(segment in current)
        ) {
            return undefined;
        }

        return (current as TranslationMap)[segment];
    }, source);
}
