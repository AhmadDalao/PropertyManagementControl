import type { ResourceField, ResourceFormValue } from './types';

export function sectionId(title: string, index: number): string {
    const slug = title
        .toLocaleLowerCase()
        .replaceAll(/[^a-z0-9]+/g, '-')
        .replaceAll(/^-|-$/g, '');

    return `form-section-${slug || index + 1}`;
}

export function groupResourceFields(fields: ResourceField[]) {
    const groups = new Map<
        string,
        {
            title: string;
            description?: string;
            fields: ResourceField[];
        }
    >();

    fields.forEach((field) => {
        const title = field.section ?? 'Details';
        const group = groups.get(title) ?? {
            title,
            description: field.sectionDescription,
            fields: [],
        };

        group.fields.push(field);
        groups.set(title, group);
    });

    return Array.from(groups.values());
}

export function fieldError(
    errors: Partial<Record<string, string>>,
    fieldName: string,
): string {
    const direct = errors[fieldName];

    if (direct) {
        return direct;
    }

    return (
        Object.entries(errors).find(
            ([key, value]) => key.startsWith(`${fieldName}.`) && Boolean(value),
        )?.[1] ?? ''
    );
}

export function isRequiredFieldComplete(
    field: ResourceField,
    value: ResourceFormValue,
): boolean {
    if (value === null || value === undefined || value === '') {
        return false;
    }

    if (field.type !== 'number') {
        return true;
    }

    const numeric = Number(value);
    const minimum =
        field.min === undefined ? Number.NEGATIVE_INFINITY : Number(field.min);

    return Number.isFinite(numeric) && numeric >= minimum;
}
