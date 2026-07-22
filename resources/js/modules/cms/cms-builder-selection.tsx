import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { CmsBuilderController } from './use-cms-builder';

export function CmsBuilderSelection({
    builder,
}: {
    builder: CmsBuilderController;
}) {
    const { t } = useTranslator();
    const selected = builder.selected;

    if (!selected) {
        return (
            <div className="pmc-empty-state">
                {t('cms.select_section_help')}
            </div>
        );
    }

    return (
        <section className="pmc-cms-selection">
            <div>
                <span>{t('cms.selected_section')}</span>
                <h3>{builder.localizedSectionName(selected.section)}</h3>
                <p>{selected.section?.name_ar}</p>
            </div>
            {(builder.selectedLibraryRecord?.page_sections_count ?? 0) > 1 ? (
                <div className="pmc-cms-shared-warning">
                    <i className="bi bi-diagram-3" />
                    {t('cms.shared_warning', undefined, {
                        count:
                            builder.selectedLibraryRecord
                                ?.page_sections_count ?? 0,
                    })}
                </div>
            ) : null}
            <div className="pmc-cms-selection-actions">
                {selected.section ? (
                    <Link
                        href={`/cms/sections/${selected.section.id}/edit`}
                        className="btn btn-primary"
                    >
                        {t('cms.edit_bilingual_content')}
                    </Link>
                ) : null}
                <button
                    type="button"
                    className="btn btn-outline-secondary"
                    disabled={builder.isBusy}
                    onClick={() => builder.toggleVisibility(selected)}
                >
                    {selected.is_visible
                        ? t('cms.hide_section')
                        : t('cms.show_section')}
                </button>
                <div>
                    <button
                        type="button"
                        className="btn btn-light"
                        disabled={
                            builder.isBusy ||
                            builder.orderedSections[0]?.id === selected.id
                        }
                        aria-label={t('cms.move_up')}
                        onClick={() => builder.moveSection(selected.id, -1)}
                    >
                        <i className="bi bi-arrow-up" />
                    </button>
                    <button
                        type="button"
                        className="btn btn-light"
                        disabled={
                            builder.isBusy ||
                            builder.orderedSections[
                                builder.orderedSections.length - 1
                            ]?.id === selected.id
                        }
                        aria-label={t('cms.move_down')}
                        onClick={() => builder.moveSection(selected.id, 1)}
                    >
                        <i className="bi bi-arrow-down" />
                    </button>
                </div>
                <button
                    type="button"
                    className="btn btn-outline-danger"
                    disabled={builder.isBusy}
                    onClick={() => builder.removeSection(selected)}
                >
                    {t('cms.remove_from_page')}
                </button>
            </div>
        </section>
    );
}
