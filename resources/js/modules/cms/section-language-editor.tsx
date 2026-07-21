import { useTranslator } from '@/lib/i18n';
import { MediaPicker } from '@/modules/media/media-picker';
import type { MediaPickerOption } from '@/modules/media/types';

import {
    addContentRow,
    removeContentRow,
    updateContentField,
    updateContentRowField,
} from './section-content-state';
import type { ContentCollection, ContentField } from './section-content-types';
import { isPlainObject, safeJsonObject, stringValue } from './section-json';

export function SectionLanguageEditor({
    language,
    fields,
    collections,
    contentJson,
    mediaOptions,
    onChange,
}: {
    language: 'en' | 'ar';
    fields: ContentField[];
    collections: ContentCollection[];
    contentJson: string;
    mediaOptions: MediaPickerOption[];
    onChange: (value: string) => void;
}) {
    const { t } = useTranslator();
    const content = safeJsonObject(contentJson);

    return (
        <article
            className="pmc-section-language"
            dir={language === 'ar' ? 'rtl' : 'ltr'}
        >
            <header>
                <strong>
                    {language === 'ar' ? t('cms.arabic') : t('cms.english')}
                </strong>
                <span>
                    {language === 'ar'
                        ? t('cms.rtl_public_copy')
                        : t('cms.public_copy')}
                </span>
            </header>

            <div className="pmc-section-field-grid">
                {fields.map((field) => (
                    <FieldControl
                        key={field.key}
                        field={field}
                        value={stringValue(content[field.key])}
                        mediaOptions={mediaOptions}
                        onChange={(value) =>
                            updateContentField(
                                contentJson,
                                onChange,
                                field.key,
                                value,
                            )
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
                    mediaOptions={mediaOptions}
                    onChange={onChange}
                />
            ))}
        </article>
    );
}

function FieldControl({
    field,
    value,
    mediaOptions,
    onChange,
}: {
    field: ContentField;
    value: string;
    mediaOptions: MediaPickerOption[];
    onChange: (value: string) => void;
}) {
    const { t } = useTranslator();

    if (field.type === 'media') {
        return (
            <div className="pmc-resource-field">
                <span>{t(`cms.fields.${field.key}`, field.label)}</span>
                <MediaPicker
                    value={value}
                    options={mediaOptions}
                    onChange={onChange}
                />
            </div>
        );
    }

    return (
        <label className="pmc-resource-field">
            <span>{t(`cms.fields.${field.key}`, field.label)}</span>
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
    mediaOptions,
    onChange,
}: {
    collection: ContentCollection;
    content: Record<string, unknown>;
    contentJson: string;
    mediaOptions: MediaPickerOption[];
    onChange: (value: string) => void;
}) {
    const { t } = useTranslator();
    const collectionValue = content[collection.key];
    const rows: unknown[] = Array.isArray(collectionValue)
        ? collectionValue
        : [];
    const collectionLabel = t(
        `cms.collections.${collection.key}`,
        collection.label,
    );
    const itemLabel = t(
        `cms.item_labels.${collection.itemLabel.toLowerCase().replaceAll(' ', '_')}`,
        collection.itemLabel,
    );

    return (
        <section className="pmc-section-collection">
            <header>
                <div>
                    <strong>{collectionLabel}</strong>
                    <span>
                        {t('cms.item_count', undefined, {
                            count: rows.length,
                        })}
                    </span>
                </div>
                <button
                    type="button"
                    className="btn btn-outline-secondary btn-sm"
                    onClick={() =>
                        addContentRow(contentJson, onChange, collection)
                    }
                >
                    <i className="bi bi-plus-lg" />
                    {t('cms.add_item')}
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
                                        {t('cms.collection_item', undefined, {
                                            label: itemLabel,
                                            number: index + 1,
                                        })}
                                    </strong>
                                    <button
                                        type="button"
                                        className="btn btn-outline-danger btn-sm"
                                        onClick={() =>
                                            removeContentRow(
                                                contentJson,
                                                onChange,
                                                collection.key,
                                                index,
                                            )
                                        }
                                    >
                                        {t('cms.remove_item')}
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
                                            mediaOptions={mediaOptions}
                                            onChange={(value) =>
                                                updateContentRowField(
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
                    {t('cms.no_collection_items', undefined, {
                        label: collectionLabel,
                    })}
                </p>
            )}
        </section>
    );
}
