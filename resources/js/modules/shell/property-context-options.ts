import type { PropertyContextOption } from '@/types';

export type PropertyContextGroup = {
    key: string;
    label: string;
    options: PropertyContextOption[];
};

export function propertyTitle(
    property: PropertyContextOption,
    locale: string,
): string {
    return locale === 'ar'
        ? property.title_ar || property.title_en || property.code
        : property.title_en || property.title_ar || property.code;
}

export function portfolioTitle(
    property: PropertyContextOption,
    locale: string,
): string {
    return locale === 'ar'
        ? property.portfolio_name_ar ||
              property.portfolio_name_en ||
              property.portfolio_code ||
              ''
        : property.portfolio_name_en ||
              property.portfolio_name_ar ||
              property.portfolio_code ||
              '';
}

export function propertyLabel(
    property: PropertyContextOption,
    locale: string,
): string {
    return `${property.code} · ${propertyTitle(property, locale)}`;
}

export function groupPropertyOptions(
    options: PropertyContextOption[],
    locale: string,
    search: string,
): PropertyContextGroup[] {
    const localeCode = locale === 'ar' ? 'ar-SA' : 'en';
    const query = search.trim().toLocaleLowerCase(localeCode);
    const groups = new Map<string, PropertyContextGroup>();

    options
        .filter((property) => matchesSearch(property, query, localeCode))
        .forEach((property) => {
            const portfolioName = portfolioTitle(property, locale);
            const portfolioCode = property.portfolio_code?.trim() ?? '';
            const key = `${portfolioCode}|${portfolioName}`;
            const label = [portfolioCode, portfolioName]
                .filter(
                    (value, index, values) =>
                        value !== '' && values.indexOf(value) === index,
                )
                .join(' · ');
            const group = groups.get(key) ?? {
                key,
                label,
                options: [],
            };

            group.options.push(property);
            groups.set(key, group);
        });

    return [...groups.values()]
        .map((group) => ({
            ...group,
            options: group.options.sort((left, right) =>
                propertyLabel(left, locale).localeCompare(
                    propertyLabel(right, locale),
                    localeCode,
                ),
            ),
        }))
        .sort((left, right) =>
            left.label.localeCompare(right.label, localeCode),
        );
}

function matchesSearch(
    property: PropertyContextOption,
    query: string,
    localeCode: string,
): boolean {
    if (query === '') {
        return true;
    }

    return [
        property.code,
        property.title_en,
        property.title_ar,
        property.portfolio_code,
        property.portfolio_name_en,
        property.portfolio_name_ar,
    ]
        .filter(Boolean)
        .join(' ')
        .toLocaleLowerCase(localeCode)
        .includes(query);
}
