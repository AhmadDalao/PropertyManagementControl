import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { dateTime } from '@/lib/utils';

import type { CmsBuilderController } from './use-cms-builder';

export function CmsBuilderInspectorPane({
    builder,
}: {
    builder: CmsBuilderController;
}) {
    const { locale, t, text } = useTranslator();

    return (
        <aside className="pmc-cms-inspector-pane">
            <header>
                <span>{t('cms.page_outline')}</span>
                <h2>{t('cms.sections')}</h2>
                <p>{t('cms.reorder_help')}</p>
            </header>

            <div className="pmc-cms-outline">
                {builder.orderedSections.map((item, index) => (
                    <article
                        key={item.id}
                        className={`${builder.selected?.id === item.id ? 'active' : ''} ${
                            builder.draggingId === item.id ? 'is-dragging' : ''
                        }`}
                        draggable
                        onDragStart={() => builder.setDraggingId(item.id)}
                        onDragOver={(event) => event.preventDefault()}
                        onDrop={() => builder.reorder(item.id)}
                        onDragEnd={() => builder.setDraggingId(null)}
                        onClick={() => builder.setSelectedId(item.id)}
                    >
                        <button
                            type="button"
                            className="pmc-cms-drag-handle"
                            aria-label={t('cms.drag_section', undefined, {
                                title: builder.localizedSectionName(
                                    item.section,
                                ),
                            })}
                        >
                            <i className="bi bi-grip-vertical" />
                        </button>
                        <div>
                            <span>
                                {index + 1}.{' '}
                                {item.section
                                    ? t(
                                          `cms.section_types.${item.section.section_type}`,
                                          item.section.section_type,
                                      )
                                    : t('cms.section')}
                            </span>
                            <strong>
                                {builder.localizedSectionName(item.section)}
                            </strong>
                        </div>
                        <span
                            className={
                                item.is_visible ? 'is-visible' : 'is-hidden'
                            }
                        >
                            {item.is_visible
                                ? t('cms.visible')
                                : t('cms.hidden')}
                        </span>
                    </article>
                ))}
            </div>

            {builder.selected ? (
                <SelectedSection builder={builder} />
            ) : (
                <div className="pmc-empty-state">
                    {t('cms.select_section_help')}
                </div>
            )}

            <details className="pmc-cms-history">
                <summary>
                    <span>{t('cms.recent_activity')}</span>
                    <strong>{builder.timeline.length}</strong>
                </summary>
                <div className="pmc-history-timeline">
                    {builder.timeline.map((event) => (
                        <div key={event.id}>
                            <span />
                            <strong>{text(event.event)}</strong>
                            <small>
                                {event.causer ?? t('cms.system_actor')} ·{' '}
                                {dateTime(event.created_at, locale)}
                            </small>
                        </div>
                    ))}
                </div>
            </details>
        </aside>
    );
}

function SelectedSection({ builder }: { builder: CmsBuilderController }) {
    const { t } = useTranslator();
    const selected = builder.selected;

    if (!selected) {
        return null;
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
                    onClick={() => builder.removeSection(selected)}
                >
                    {t('cms.remove_from_page')}
                </button>
            </div>
        </section>
    );
}
