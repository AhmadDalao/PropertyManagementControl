import type { ContentCollection } from './section-content-types';
import { isPlainObject, jsonText, safeJsonObject } from './section-json';

type ContentChange = (value: string) => void;

export function updateContentField(
    contentJson: string,
    onChange: ContentChange,
    key: string,
    value: string,
) {
    const next = safeJsonObject(contentJson);
    next[key] = value;
    onChange(jsonText(next));
}

export function addContentRow(
    contentJson: string,
    onChange: ContentChange,
    collection: ContentCollection,
) {
    const next = safeJsonObject(contentJson);
    const rows = collectionRows(next, collection.key);

    rows.push(
        Object.fromEntries(
            collection.fields.map((field) => [
                field.key,
                field.key === 'icon' ? 'bi-grid' : '',
            ]),
        ),
    );
    onChange(jsonText({ ...next, [collection.key]: rows }));
}

export function removeContentRow(
    contentJson: string,
    onChange: ContentChange,
    collectionKey: string,
    index: number,
) {
    const next = safeJsonObject(contentJson);
    const rows = collectionRows(next, collectionKey);

    rows.splice(index, 1);
    onChange(jsonText({ ...next, [collectionKey]: rows }));
}

export function updateContentRowField(
    contentJson: string,
    onChange: ContentChange,
    collectionKey: string,
    index: number,
    fieldKey: string,
    value: string,
) {
    const next = safeJsonObject(contentJson);
    const rows = collectionRows(next, collectionKey);
    const row = isPlainObject(rows[index]) ? rows[index] : {};

    rows[index] = { ...row, [fieldKey]: value };
    onChange(jsonText({ ...next, [collectionKey]: rows }));
}

function collectionRows(content: Record<string, unknown>, key: string) {
    return Array.isArray(content[key]) ? [...content[key]] : [];
}
