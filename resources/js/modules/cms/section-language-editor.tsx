import { useTranslator } from '@/lib/i18n';
import type { MediaPickerOption } from '@/modules/media/types';

import { SectionCollectionEditor } from './section-collection-editor';
import { updateContentField } from './section-content-state';
import type { ContentCollection, ContentField } from './section-content-types';
import { SectionFieldControl } from './section-field-control';
import { safeJsonObject, stringValue } from './section-json';

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
                    <SectionFieldControl
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
                <SectionCollectionEditor
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
