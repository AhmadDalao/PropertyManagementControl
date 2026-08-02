import { usePage } from '@inertiajs/react';

import type { SharedProps, TranslationMap } from '@/types';

export type UiTranslationKey =
    | `actions.${string}`
    | `action_center.${string}`
    | `assets.${string}`
    | `audit.${string}`
    | `auth.${string}`
    | `backups.${string}`
    | `cms.${string}`
    | `company_control.${string}`
    | `common.${string}`
    | `dashboard.${string}`
    | `daily_reports.${string}`
    | `documents.${string}`
    | `docs.${string}`
    | `email_delivery.${string}`
    | `errors.${string}`
    | `expenses.${string}`
    | `fields.${string}`
    | `filters.${string}`
    | `leases.${string}`
    | `lease_renewals.${string}`
    | `lease_move_outs.${string}`
    | `map.${string}`
    | `login.${string}`
    | `messages.${string}`
    | `maintenance.${string}`
    | `maintenance_vendors.${string}`
    | `media.${string}`
    | `modules.${string}`
    | `nav.${string}`
    | `notifications.${string}`
    | `opening_data.${string}`
    | `pagination.${string}`
    | `passwords.${string}`
    | `payments.${string}`
    | `portfolio_control.${string}`
    | `portfolios.${string}`
    | `profile.${string}`
    | `public.${string}`
    | `reports.${string}`
    | `rent_collection.${string}`
    | `readiness.${string}`
    | `resource.${string}`
    | `roles.${string}`
    | `search.${string}`
    | `showcase.${string}`
    | `shell.${string}`
    | `status.${string}`
    | `table.${string}`
    | `tenants.${string}`
    | `users.${string}`
    | `validation.${string}`
    | `wording.${string}`
    | `work_orders.${string}`;

export type LocalizedCopy = {
    key: UiTranslationKey | string;
    fallback?: string;
    replacements?: Record<string, string | number>;
};

export type CopyValue = LocalizedCopy | string;

export type Translator = (
    key: UiTranslationKey,
    fallback?: string,
    replacements?: Record<string, string | number>,
) => string;

export function useTranslator() {
    const { app } = usePage<SharedProps>().props;

    const t: Translator = (
        key: UiTranslationKey,
        fallback?: string,
        replacements: Record<string, string | number> = {},
    ): string => {
        const value = lookup(app.translations, key);
        const translated =
            typeof value === 'string' ? value : (fallback ?? key);

        return replaceTokens(translated, replacements);
    };

    const text = (value: string): string => {
        const textTranslations = app.translations.text as
            TranslationMap | undefined;
        const directTranslation =
            textTranslations !== undefined && value in textTranslations
                ? textTranslations[value]
                : undefined;

        return typeof directTranslation === 'string'
            ? directTranslation
            : value;
    };

    const copy = (value: CopyValue): string => {
        if (typeof value === 'string') {
            return text(value);
        }

        return t(
            value.key as UiTranslationKey,
            value.fallback,
            value.replacements,
        );
    };

    return {
        copy,
        direction: app.direction,
        locale: app.locale,
        t,
        text,
    };
}

export function replaceTokens(
    value: string,
    replacements: Record<string, string | number>,
): string {
    return Object.entries(replacements)
        .sort(([left], [right]) => right.length - left.length)
        .reduce(
            (copy, [name, replacement]) =>
                copy.replaceAll(`:${name}`, String(replacement)),
            value,
        );
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
