import { useEffect, useRef } from 'react';

import { useTranslator } from '@/lib/i18n';
import type { MediaPickerOption } from '@/modules/media/types';

import { SectionContentEditor } from './section-content-editor';
import type { CmsSectionRecord } from './types';
import { useCmsBuilderSectionEditor } from './use-cms-builder-section-editor';

export function CmsBuilderSectionEditor({
    pageId,
    section,
    mediaOptions,
    sharedCount,
    onClose,
}: {
    pageId: number;
    section: CmsSectionRecord;
    mediaOptions: MediaPickerOption[];
    sharedCount: number;
    onClose: () => void;
}) {
    const { locale, t } = useTranslator();
    const closeButtonRef = useRef<HTMLButtonElement>(null);
    const controller = useCmsBuilderSectionEditor(pageId, section, onClose);
    const sectionTitle =
        locale === 'ar'
            ? section.name_ar || section.name_en || t('cms.missing_section')
            : section.name_en || section.name_ar || t('cms.missing_section');

    useEffect(() => {
        const previousFocus = document.activeElement as HTMLElement | null;
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape' && !controller.form.processing) {
                onClose();
            }
        };

        document.body.classList.add('pmc-cms-editor-open');
        document.addEventListener('keydown', handleKeyDown);
        closeButtonRef.current?.focus();

        return () => {
            document.body.classList.remove('pmc-cms-editor-open');
            document.removeEventListener('keydown', handleKeyDown);
            previousFocus?.focus();
        };
    }, [controller.form.processing, onClose]);

    const firstError = Object.values(controller.form.errors)[0];

    return (
        <div
            className="pmc-cms-editor-backdrop"
            onMouseDown={(event) => {
                if (
                    event.target === event.currentTarget &&
                    !controller.form.processing
                ) {
                    onClose();
                }
            }}
        >
            <section
                className="pmc-cms-editor-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="cms-editor-title"
            >
                <header>
                    <div>
                        <span>{t('cms.edit_in_builder')}</span>
                        <h2 id="cms-editor-title">
                            {t('cms.edit_section', undefined, {
                                title: sectionTitle,
                            })}
                        </h2>
                        <p>{t('cms.edit_in_builder_help')}</p>
                    </div>
                    <button
                        ref={closeButtonRef}
                        type="button"
                        className="btn btn-light"
                        aria-label={t('actions.close')}
                        disabled={controller.form.processing}
                        onClick={onClose}
                    >
                        <i className="bi bi-x-lg" />
                    </button>
                </header>

                <form onSubmit={controller.submit}>
                    {sharedCount > 1 ? (
                        <div className="pmc-cms-shared-warning">
                            <i className="bi bi-diagram-3" />
                            {t('cms.shared_warning', undefined, {
                                count: sharedCount,
                            })}
                        </div>
                    ) : null}

                    <div className="pmc-cms-editor-identity">
                        <label className="pmc-resource-field">
                            <span>{t('cms.name_en')}</span>
                            <input
                                className="form-control"
                                value={controller.form.data.name_en}
                                required
                                onChange={(event) =>
                                    controller.form.setData(
                                        'name_en',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                        </label>
                        <label className="pmc-resource-field" dir="rtl">
                            <span>{t('cms.name_ar')}</span>
                            <input
                                className="form-control"
                                value={controller.form.data.name_ar}
                                required
                                onChange={(event) =>
                                    controller.form.setData(
                                        'name_ar',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                        </label>
                        <label className="pmc-resource-field">
                            <span>{t('cms.status')}</span>
                            <select
                                className="form-select"
                                value={controller.form.data.status}
                                onChange={(event) =>
                                    controller.form.setData(
                                        'status',
                                        event.currentTarget.value,
                                    )
                                }
                            >
                                {['active', 'inactive', 'archived'].map(
                                    (status) => (
                                        <option key={status} value={status}>
                                            {t(`status.${status}`)}
                                        </option>
                                    ),
                                )}
                            </select>
                        </label>
                    </div>

                    <SectionContentEditor
                        sectionType={section.section_type}
                        contentEnJson={controller.form.data.content_en_json}
                        contentArJson={controller.form.data.content_ar_json}
                        mediaOptions={mediaOptions}
                        onContentEnChange={(value) =>
                            controller.form.setData('content_en_json', value)
                        }
                        onContentArChange={(value) =>
                            controller.form.setData('content_ar_json', value)
                        }
                    />

                    {controller.contentError || firstError ? (
                        <div className="alert alert-danger" role="alert">
                            {controller.contentError || firstError}
                        </div>
                    ) : null}

                    <footer>
                        <button
                            type="button"
                            className="btn btn-light"
                            disabled={controller.form.processing}
                            onClick={onClose}
                        >
                            {t('actions.cancel')}
                        </button>
                        <button
                            type="submit"
                            className="btn btn-primary"
                            disabled={controller.form.processing}
                        >
                            {controller.form.processing
                                ? t('cms.saving')
                                : t('cms.save_and_preview')}
                        </button>
                    </footer>
                </form>
            </section>
        </div>
    );
}
