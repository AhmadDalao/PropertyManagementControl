import { useTranslator } from '@/lib/i18n';
import type { MediaPickerOption } from '@/modules/media/types';

import {
    addContentRow,
    removeContentRow,
    updateContentRowField,
} from './section-content-state';
import type { ContentCollection } from './section-content-types';
import { SectionFieldControl } from './section-field-control';
import { isPlainObject, stringValue } from './section-json';

export function SectionCollectionEditor({
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
                        {t('cms.item_count', undefined, { count: rows.length })}
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
                                        <SectionFieldControl
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
