import type { ContentCollection, ContentField } from './section-schema';
import {
    defaultSectionContent,
    isPlainObject,
    jsonText,
    readableSectionType,
    safeJsonObject,
    sectionContentSchema,
    stringValue,
} from './section-schema';

export function SectionContentEditor({
    sectionType,
    contentEnJson,
    contentArJson,
    onContentEnChange,
    onContentArChange,
}: {
    sectionType: string;
    contentEnJson: string;
    contentArJson: string;
    onContentEnChange: (value: string) => void;
    onContentArChange: (value: string) => void;
}) {
    const schema = sectionContentSchema(sectionType);
    const loadTemplate = () => {
        onContentEnChange(jsonText(defaultSectionContent(sectionType, 'en')));
        onContentArChange(jsonText(defaultSectionContent(sectionType, 'ar')));
    };

    return (
        <section className="pmc-section-editor">
            <header className="pmc-section-editor-head">
                <div>
                    <span>Guided bilingual content</span>
                    <h2>{readableSectionType(sectionType)}</h2>
                    <p>{schema.description}</p>
                </div>
                <button
                    type="button"
                    className="btn btn-outline-secondary"
                    onClick={loadTemplate}
                >
                    <i className="bi bi-stars" />
                    Load starter content
                </button>
            </header>

            <div className="pmc-section-language-grid">
                <LanguageEditor
                    language="en"
                    fields={schema.fields}
                    collections={schema.collections}
                    contentJson={contentEnJson}
                    onChange={onContentEnChange}
                />
                <LanguageEditor
                    language="ar"
                    fields={schema.fields}
                    collections={schema.collections}
                    contentJson={contentArJson}
                    onChange={onContentArChange}
                />
            </div>

            <details className="pmc-section-json">
                <summary>
                    <i className="bi bi-braces" />
                    Advanced JSON
                </summary>
                <div>
                    <label>
                        <span>English JSON</span>
                        <textarea
                            className="form-control pmc-code-textarea"
                            rows={12}
                            value={contentEnJson}
                            onChange={(event) =>
                                onContentEnChange(event.currentTarget.value)
                            }
                        />
                    </label>
                    <label>
                        <span>Arabic JSON</span>
                        <textarea
                            className="form-control pmc-code-textarea"
                            rows={12}
                            dir="rtl"
                            value={contentArJson}
                            onChange={(event) =>
                                onContentArChange(event.currentTarget.value)
                            }
                        />
                    </label>
                </div>
            </details>
        </section>
    );
}

function LanguageEditor({
    language,
    fields,
    collections,
    contentJson,
    onChange,
}: {
    language: 'en' | 'ar';
    fields: ContentField[];
    collections: ContentCollection[];
    contentJson: string;
    onChange: (value: string) => void;
}) {
    const content = safeJsonObject(contentJson);

    return (
        <article
            className="pmc-section-language"
            dir={language === 'ar' ? 'rtl' : 'ltr'}
        >
            <header>
                <strong>{language === 'ar' ? 'Arabic' : 'English'}</strong>
                <span>
                    {language === 'ar' ? 'RTL public copy' : 'Public copy'}
                </span>
            </header>

            <div className="pmc-section-field-grid">
                {fields.map((field) => (
                    <FieldControl
                        key={field.key}
                        field={field}
                        value={stringValue(content[field.key])}
                        onChange={(value) =>
                            updateField(contentJson, onChange, field.key, value)
                        }
                    />
                ))}
            </div>

            {collections.map((collection) => (
                <CollectionEditor
                    key={collection.key}
                    collection={collection}
                    content={content}
                    contentJson={contentJson}
                    onChange={onChange}
                />
            ))}
        </article>
    );
}

function FieldControl({
    field,
    value,
    onChange,
}: {
    field: ContentField;
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <label className="pmc-resource-field">
            <span>{field.label}</span>
            {field.type === 'textarea' ? (
                <textarea
                    className="form-control"
                    rows={3}
                    value={value}
                    onChange={(event) => onChange(event.currentTarget.value)}
                />
            ) : (
                <input
                    className="form-control"
                    value={value}
                    onChange={(event) => onChange(event.currentTarget.value)}
                />
            )}
        </label>
    );
}

function CollectionEditor({
    collection,
    content,
    contentJson,
    onChange,
}: {
    collection: ContentCollection;
    content: Record<string, unknown>;
    contentJson: string;
    onChange: (value: string) => void;
}) {
    const rows = Array.isArray(content[collection.key])
        ? (content[collection.key] as unknown[])
        : [];

    return (
        <section className="pmc-section-collection">
            <header>
                <div>
                    <strong>{collection.label}</strong>
                    <span>{rows.length} items</span>
                </div>
                <button
                    type="button"
                    className="btn btn-outline-secondary btn-sm"
                    onClick={() => addRow(contentJson, onChange, collection)}
                >
                    <i className="bi bi-plus-lg" />
                    Add
                </button>
            </header>

            {rows.length > 0 ? (
                <div className="pmc-section-collection-list">
                    {rows.map((row, index) => {
                        const values = isPlainObject(row) ? row : {};

                        return (
                            <article key={`${collection.key}-${index}`}>
                                <header>
                                    <strong>
                                        {collection.itemLabel} {index + 1}
                                    </strong>
                                    <button
                                        type="button"
                                        className="btn btn-outline-danger btn-sm"
                                        onClick={() =>
                                            removeRow(
                                                contentJson,
                                                onChange,
                                                collection.key,
                                                index,
                                            )
                                        }
                                    >
                                        Remove
                                    </button>
                                </header>
                                <div className="pmc-section-field-grid">
                                    {collection.fields.map((field) => (
                                        <FieldControl
                                            key={field.key}
                                            field={field}
                                            value={stringValue(
                                                values[field.key],
                                            )}
                                            onChange={(value) =>
                                                updateRowField(
                                                    contentJson,
                                                    onChange,
                                                    collection.key,
                                                    index,
                                                    field.key,
                                                    value,
                                                )
                                            }
                                        />
                                    ))}
                                </div>
                            </article>
                        );
                    })}
                </div>
            ) : (
                <p className="pmc-inline-empty">
                    No {collection.label.toLowerCase()} yet.
                </p>
            )}
        </section>
    );
}

function updateField(
    contentJson: string,
    onChange: (value: string) => void,
    key: string,
    value: string,
) {
    const next = safeJsonObject(contentJson);
    next[key] = value;
    onChange(jsonText(next));
}

function addRow(
    contentJson: string,
    onChange: (value: string) => void,
    collection: ContentCollection,
) {
    const next = safeJsonObject(contentJson);
    const rows = Array.isArray(next[collection.key])
        ? [...(next[collection.key] as unknown[])]
        : [];

    rows.push(
        Object.fromEntries(
            collection.fields.map((field) => [
                field.key,
                field.key === 'icon' ? 'bi-grid' : '',
            ]),
        ),
    );
    next[collection.key] = rows;
    onChange(jsonText(next));
}

function removeRow(
    contentJson: string,
    onChange: (value: string) => void,
    collectionKey: string,
    index: number,
) {
    const next = safeJsonObject(contentJson);
    const rows = Array.isArray(next[collectionKey])
        ? [...(next[collectionKey] as unknown[])]
        : [];

    rows.splice(index, 1);
    next[collectionKey] = rows;
    onChange(jsonText({ ...next, [collectionKey]: rows }));
}

function updateRowField(
    contentJson: string,
    onChange: (value: string) => void,
    collectionKey: string,
    index: number,
    fieldKey: string,
    value: string,
) {
    const next = safeJsonObject(contentJson);
    const rows = Array.isArray(next[collectionKey])
        ? [...(next[collectionKey] as unknown[])]
        : [];
    const row = isPlainObject(rows[index]) ? rows[index] : {};

    rows[index] = { ...row, [fieldKey]: value };
    onChange(jsonText({ ...next, [collectionKey]: rows }));
}
