import { useTranslator } from '@/lib/i18n';
import type { MediaPickerOption } from '@/modules/media/types';

import { sectionContentSchema } from './section-content-schema-definition';
import { defaultSectionContent } from './section-content-templates';
import { jsonText, readableSectionType } from './section-json';
import { SectionLanguageEditor } from './section-language-editor';

export function SectionContentEditor({
    sectionType,
    contentEnJson,
    contentArJson,
    mediaOptions,
    onContentEnChange,
    onContentArChange,
}: {
    sectionType: string;
    contentEnJson: string;
    contentArJson: string;
    mediaOptions: MediaPickerOption[];
    onContentEnChange: (value: string) => void;
    onContentArChange: (value: string) => void;
}) {
    const { t } = useTranslator();
    const schema = sectionContentSchema(sectionType);
    const loadTemplate = () => {
        onContentEnChange(jsonText(defaultSectionContent(sectionType, 'en')));
        onContentArChange(jsonText(defaultSectionContent(sectionType, 'ar')));
    };

    return (
        <section className="pmc-section-editor">
            <header className="pmc-section-editor-head">
                <div>
                    <span>{t('cms.guided_content')}</span>
                    <h2>
                        {t(
                            `cms.section_types.${sectionType}`,
                            readableSectionType(sectionType),
                        )}
                    </h2>
                    <p>
                        {t(
                            `cms.section_descriptions.${sectionType}`,
                            schema.description,
                        )}
                    </p>
                </div>
                <button
                    type="button"
                    className="btn btn-outline-secondary"
                    onClick={loadTemplate}
                >
                    <i className="bi bi-stars" />
                    {t('cms.load_starter_content')}
                </button>
            </header>

            <div className="pmc-section-language-grid">
                <SectionLanguageEditor
                    language="en"
                    fields={schema.fields}
                    collections={schema.collections}
                    contentJson={contentEnJson}
                    mediaOptions={mediaOptions}
                    onChange={onContentEnChange}
                />
                <SectionLanguageEditor
                    language="ar"
                    fields={schema.fields}
                    collections={schema.collections}
                    contentJson={contentArJson}
                    mediaOptions={mediaOptions}
                    onChange={onContentArChange}
                />
            </div>

            <details className="pmc-section-json">
                <summary>
                    <i className="bi bi-braces" />
                    {t('cms.advanced_json')}
                </summary>
                <div>
                    <JsonEditor
                        label={t('cms.english_json')}
                        value={contentEnJson}
                        onChange={onContentEnChange}
                    />
                    <JsonEditor
                        label={t('cms.arabic_json')}
                        value={contentArJson}
                        onChange={onContentArChange}
                        direction="rtl"
                    />
                </div>
            </details>
        </section>
    );
}

function JsonEditor({
    label,
    value,
    onChange,
    direction,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    direction?: 'rtl';
}) {
    return (
        <label>
            <span>{label}</span>
            <textarea
                className="form-control pmc-code-textarea"
                rows={12}
                dir={direction}
                value={value}
                onChange={(event) => onChange(event.currentTarget.value)}
            />
        </label>
    );
}
