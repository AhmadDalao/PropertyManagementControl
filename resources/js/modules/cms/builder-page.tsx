import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { CmsRenderer } from '@/components/cms-renderer';
import { ResourceHeader } from '@/components/resource-cycle';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { dateTime } from '@/lib/utils';
import type { SharedProps } from '@/types';

type CmsContent = Record<string, unknown>;

type CmsSection = {
    id: number;
    section_type: string;
    name_en: string;
    name_ar: string;
    status: string;
    content_en?: CmsContent | null;
    content_ar?: CmsContent | null;
    page_sections_count?: number;
};

type CmsPageSection = {
    id: number;
    cms_page_id: number;
    cms_section_id: number;
    sort_order: number;
    is_visible: boolean;
    settings_json?: Record<string, FormDataConvertible> | null;
    section?: CmsSection | null;
};

type CmsPage = {
    id: number;
    title_en: string;
    title_ar: string;
    slug: string;
    status: string;
    is_homepage: boolean;
    is_visible: boolean;
    published_at?: string | null;
    page_sections: CmsPageSection[];
};

type PageProps = SharedProps & {
    page: CmsPage;
    sections: CmsSection[];
    timeline: Array<{
        id: number;
        event: string;
        causer?: string;
        created_at?: string;
    }>;
};

type BuilderPanel = 'sections' | 'preview' | 'settings';
type SaveState = 'saved' | 'saving' | 'error';

export default function CmsBuilderPage() {
    const { props } = usePage<PageProps>();
    const { locale, t, text } = useTranslator();
    const sortedFromServer = [...props.page.page_sections].sort(
        (a, b) => a.sort_order - b.sort_order,
    );
    const [orderedSections, setOrderedSections] =
        useState<CmsPageSection[]>(sortedFromServer);
    const [selectedId, setSelectedId] = useState<number | null>(
        sortedFromServer[0]?.id ?? null,
    );
    const [draggingId, setDraggingId] = useState<number | null>(null);
    const [mobilePanel, setMobilePanel] = useState<BuilderPanel>('sections');
    const [previewLocale, setPreviewLocale] = useState<'en' | 'ar'>('en');
    const [previewWidth, setPreviewWidth] = useState<'desktop' | 'mobile'>(
        'desktop',
    );
    const [saveState, setSaveState] = useState<SaveState>('saved');
    const attachForm = useForm({
        cms_section_id: String(props.sections[0]?.id ?? ''),
        sort_order: String((props.page.page_sections.length || 0) + 1),
        is_visible: true,
    });

    const selected =
        orderedSections.find((item) => item.id === selectedId) ??
        orderedSections[0] ??
        null;
    const selectedLibraryRecord = props.sections.find(
        (section) => section.id === selected?.cms_section_id,
    );
    const visibleSections = orderedSections.filter(
        (item) => item.is_visible && item.section,
    );
    const localizedPageTitle =
        locale === 'ar'
            ? props.page.title_ar || props.page.title_en
            : props.page.title_en || props.page.title_ar;
    const localizedSectionName = (
        section: CmsSection | null | undefined,
        targetLocale: 'en' | 'ar' = locale === 'ar' ? 'ar' : 'en',
    ) => {
        if (!section) {
            return t('cms.missing_section');
        }

        return targetLocale === 'ar'
            ? section.name_ar || section.name_en
            : section.name_en || section.name_ar;
    };

    const persistSectionOrder = (
        nextSections: CmsPageSection[],
        previousSections = orderedSections,
    ) => {
        setOrderedSections(
            nextSections.map((item, index) => ({
                ...item,
                sort_order: index + 1,
            })),
        );
        setSaveState('saving');
        router.put(
            `/cms/pages/${props.page.id}/sections/reorder`,
            { ordered_ids: nextSections.map((item) => item.id) },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => setSaveState('saved'),
                onError: () => {
                    setOrderedSections(previousSections);
                    setSaveState('error');
                },
            },
        );
    };

    const attachSection = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        attachForm.post(`/cms/pages/${props.page.id}/sections`, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => setSaveState('saved'),
        });
    };

    const reorder = (targetId: number) => {
        if (!draggingId || draggingId === targetId) {
            setDraggingId(null);

            return;
        }

        const previous = [...orderedSections];
        const next = [...orderedSections];
        const fromIndex = next.findIndex((item) => item.id === draggingId);
        const toIndex = next.findIndex((item) => item.id === targetId);

        if (fromIndex === -1 || toIndex === -1) {
            setDraggingId(null);

            return;
        }

        next.splice(toIndex, 0, next.splice(fromIndex, 1)[0]);
        setDraggingId(null);
        persistSectionOrder(next, previous);
    };

    const moveSection = (sectionId: number, direction: -1 | 1) => {
        const previous = [...orderedSections];
        const next = [...orderedSections];
        const currentIndex = next.findIndex((item) => item.id === sectionId);
        const nextIndex = currentIndex + direction;

        if (currentIndex === -1 || nextIndex < 0 || nextIndex >= next.length) {
            return;
        }

        [next[currentIndex], next[nextIndex]] = [
            next[nextIndex],
            next[currentIndex],
        ];
        persistSectionOrder(next, previous);
    };

    const toggleVisibility = (item: CmsPageSection) => {
        const previous = [...orderedSections];
        setOrderedSections((current) =>
            current.map((section) =>
                section.id === item.id
                    ? { ...section, is_visible: !section.is_visible }
                    : section,
            ),
        );
        setSaveState('saving');
        router.put(
            `/cms/page-sections/${item.id}`,
            {
                sort_order: item.sort_order,
                is_visible: !item.is_visible,
                settings_json: item.settings_json ?? {},
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => setSaveState('saved'),
                onError: () => {
                    setOrderedSections(previous);
                    setSaveState('error');
                },
            },
        );
    };

    return (
        <AdminLayout>
            <Head title={`${t('cms.builder')} · ${localizedPageTitle}`} />
            <ResourceHeader
                eyebrow={t('cms.builder')}
                title={localizedPageTitle}
                description={t('cms.page_summary', undefined, {
                    status: t(`status.${props.page.status}`, props.page.status),
                    count: orderedSections.length,
                })}
                backHref="/cms"
                backLabel={t('cms.website_control')}
                actions={[
                    {
                        label: t('cms.edit_page_settings'),
                        href: `/cms/pages/${props.page.id}/edit`,
                        variant: 'primary',
                    },
                    {
                        label: t('cms.open_public_preview'),
                        href: props.page.is_homepage
                            ? '/'
                            : `/pages/${props.page.slug}`,
                        variant: 'secondary',
                    },
                ]}
            />

            <div className="pmc-cms-builder-toolbar">
                <div className="pmc-cms-builder-status">
                    <span className={`is-${saveState}`} aria-live="polite" />
                    <strong>
                        {saveState === 'saving'
                            ? t('cms.saving')
                            : saveState === 'error'
                              ? t('cms.save_failed')
                              : t('cms.saved')}
                    </strong>
                </div>
                <div className="pmc-cms-mobile-tabs">
                    {(['sections', 'preview', 'settings'] as const).map(
                        (panel) => (
                            <button
                                key={panel}
                                type="button"
                                className={
                                    mobilePanel === panel ? 'active' : ''
                                }
                                onClick={() => setMobilePanel(panel)}
                            >
                                {t(`cms.panel_${panel}`)}
                            </button>
                        ),
                    )}
                </div>
                <div className="pmc-cms-builder-pills">
                    <span>
                        {t(`status.${props.page.status}`, props.page.status)}
                    </span>
                    <span>
                        {props.page.is_homepage
                            ? t('cms.homepage')
                            : t('cms.standard_page')}
                    </span>
                    <span>
                        {t('cms.visible_count', undefined, {
                            count: visibleSections.length,
                        })}
                    </span>
                </div>
            </div>

            <section
                className="pmc-cms-builder-workspace"
                data-mobile-panel={mobilePanel}
            >
                <aside className="pmc-cms-library-pane">
                    <header>
                        <span>{t('cms.section_library')}</span>
                        <h2>{t('cms.add_content')}</h2>
                        <p>{t('cms.attach_help')}</p>
                    </header>
                    <form onSubmit={attachSection}>
                        <label
                            className="pmc-resource-field"
                            htmlFor="cms-section-library"
                        >
                            <span>{t('cms.sections')}</span>
                            <select
                                id="cms-section-library"
                                className="form-select"
                                value={attachForm.data.cms_section_id}
                                onChange={(event) =>
                                    attachForm.setData(
                                        'cms_section_id',
                                        event.currentTarget.value,
                                    )
                                }
                            >
                                {props.sections.map((section) => (
                                    <option key={section.id} value={section.id}>
                                        {localizedSectionName(section)} ·{' '}
                                        {t(
                                            `cms.section_types.${section.section_type}`,
                                            section.section_type,
                                        )}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="pmc-resource-check">
                            <input
                                type="checkbox"
                                checked={attachForm.data.is_visible}
                                onChange={(event) =>
                                    attachForm.setData(
                                        'is_visible',
                                        event.currentTarget.checked,
                                    )
                                }
                            />
                            <span>
                                <strong>
                                    {t('cms.visible_after_attaching')}
                                </strong>
                            </span>
                        </label>
                        <button
                            className="btn btn-primary"
                            disabled={attachForm.processing}
                        >
                            <i className="bi bi-plus-lg" />
                            {t('cms.attach_section')}
                        </button>
                        <Link
                            href="/cms/sections/create"
                            className="btn btn-outline-secondary"
                        >
                            {t('cms.create_new_section')}
                        </Link>
                    </form>

                    <div className="pmc-cms-library-list">
                        {props.sections.slice(0, 12).map((section) => (
                            <article key={section.id}>
                                <i className="bi bi-layout-text-window" />
                                <div>
                                    <span>
                                        {t(
                                            `cms.section_types.${section.section_type}`,
                                            section.section_type,
                                        )}
                                    </span>
                                    <strong>
                                        {localizedSectionName(section)}
                                    </strong>
                                    <small>
                                        {locale === 'ar'
                                            ? section.name_en
                                            : section.name_ar}
                                    </small>
                                </div>
                                <em>
                                    {t('cms.uses', undefined, {
                                        count: section.page_sections_count ?? 0,
                                    })}
                                </em>
                            </article>
                        ))}
                    </div>
                </aside>

                <main className="pmc-cms-preview-pane">
                    <header className="pmc-cms-preview-toolbar">
                        <div>
                            <span>{t('cms.live_canvas')}</span>
                            <strong>
                                {previewLocale === 'ar'
                                    ? props.page.title_ar
                                    : props.page.title_en}
                            </strong>
                        </div>
                        <div>
                            <div
                                role="group"
                                aria-label={t('cms.preview_language')}
                            >
                                {(['en', 'ar'] as const).map((locale) => (
                                    <button
                                        key={locale}
                                        type="button"
                                        className={
                                            previewLocale === locale
                                                ? 'active'
                                                : ''
                                        }
                                        onClick={() => setPreviewLocale(locale)}
                                    >
                                        {locale.toUpperCase()}
                                    </button>
                                ))}
                            </div>
                            <div
                                role="group"
                                aria-label={t('cms.preview_width')}
                            >
                                <button
                                    type="button"
                                    className={
                                        previewWidth === 'desktop'
                                            ? 'active'
                                            : ''
                                    }
                                    aria-label={t('cms.desktop_preview')}
                                    onClick={() => setPreviewWidth('desktop')}
                                >
                                    <i className="bi bi-display" />
                                </button>
                                <button
                                    type="button"
                                    className={
                                        previewWidth === 'mobile'
                                            ? 'active'
                                            : ''
                                    }
                                    aria-label={t('cms.mobile_preview')}
                                    onClick={() => setPreviewWidth('mobile')}
                                >
                                    <i className="bi bi-phone" />
                                </button>
                            </div>
                        </div>
                    </header>
                    <div
                        className={`pmc-cms-preview-frame is-${previewWidth}`}
                        dir={previewLocale === 'ar' ? 'rtl' : 'ltr'}
                        lang={previewLocale}
                    >
                        <div className="pmc-cms-preview-document">
                            {visibleSections.length > 0 ? (
                                <CmsRenderer
                                    sections={visibleSections}
                                    locale={previewLocale}
                                />
                            ) : (
                                <div className="pmc-empty-state">
                                    <i className="bi bi-layout-text-window" />
                                    <strong>
                                        {t('cms.no_visible_sections')}
                                    </strong>
                                    <span>
                                        {t('cms.no_visible_sections_help')}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                </main>

                <aside className="pmc-cms-inspector-pane">
                    <header>
                        <span>{t('cms.page_outline')}</span>
                        <h2>{t('cms.sections')}</h2>
                        <p>{t('cms.reorder_help')}</p>
                    </header>

                    <div className="pmc-cms-outline">
                        {orderedSections.map((item, index) => (
                            <article
                                key={item.id}
                                className={`${selected?.id === item.id ? 'active' : ''} ${
                                    draggingId === item.id ? 'is-dragging' : ''
                                }`}
                                draggable
                                onDragStart={() => setDraggingId(item.id)}
                                onDragOver={(event) => event.preventDefault()}
                                onDrop={() => reorder(item.id)}
                                onDragEnd={() => setDraggingId(null)}
                                onClick={() => setSelectedId(item.id)}
                            >
                                <button
                                    type="button"
                                    className="pmc-cms-drag-handle"
                                    aria-label={t(
                                        'cms.drag_section',
                                        undefined,
                                        {
                                            title: localizedSectionName(
                                                item.section,
                                            ),
                                        },
                                    )}
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
                                        {localizedSectionName(item.section)}
                                    </strong>
                                </div>
                                <span
                                    className={
                                        item.is_visible
                                            ? 'is-visible'
                                            : 'is-hidden'
                                    }
                                >
                                    {item.is_visible
                                        ? t('cms.visible')
                                        : t('cms.hidden')}
                                </span>
                            </article>
                        ))}
                    </div>

                    {selected ? (
                        <section className="pmc-cms-selection">
                            <div>
                                <span>{t('cms.selected_section')}</span>
                                <h3>
                                    {localizedSectionName(selected.section)}
                                </h3>
                                <p>{selected.section?.name_ar}</p>
                            </div>
                            {(selectedLibraryRecord?.page_sections_count ?? 0) >
                            1 ? (
                                <div className="pmc-cms-shared-warning">
                                    <i className="bi bi-diagram-3" />
                                    {t('cms.shared_warning', undefined, {
                                        count:
                                            selectedLibraryRecord?.page_sections_count ??
                                            0,
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
                                    onClick={() => toggleVisibility(selected)}
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
                                            orderedSections[0]?.id ===
                                            selected.id
                                        }
                                        aria-label={t('cms.move_up')}
                                        onClick={() =>
                                            moveSection(selected.id, -1)
                                        }
                                    >
                                        <i className="bi bi-arrow-up" />
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-light"
                                        disabled={
                                            orderedSections[
                                                orderedSections.length - 1
                                            ]?.id === selected.id
                                        }
                                        aria-label={t('cms.move_down')}
                                        onClick={() =>
                                            moveSection(selected.id, 1)
                                        }
                                    >
                                        <i className="bi bi-arrow-down" />
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    className="btn btn-outline-danger"
                                    onClick={() => {
                                        if (
                                            window.confirm(
                                                t('cms.remove_confirm'),
                                            )
                                        ) {
                                            router.delete(
                                                `/cms/page-sections/${selected.id}`,
                                                {
                                                    preserveScroll: true,
                                                    preserveState: false,
                                                },
                                            );
                                        }
                                    }}
                                >
                                    {t('cms.remove_from_page')}
                                </button>
                            </div>
                        </section>
                    ) : (
                        <div className="pmc-empty-state">
                            {t('cms.select_section_help')}
                        </div>
                    )}

                    <details className="pmc-cms-history">
                        <summary>
                            <span>{t('cms.recent_activity')}</span>
                            <strong>{props.timeline.length}</strong>
                        </summary>
                        <div className="pmc-history-timeline">
                            {props.timeline.map((event) => (
                                <div key={event.id}>
                                    <span />
                                    <strong>{text(event.event)}</strong>
                                    <small>
                                        {event.causer ?? t('cms.system_actor')}{' '}
                                        · {dateTime(event.created_at, locale)}
                                    </small>
                                </div>
                            ))}
                        </div>
                    </details>
                </aside>
            </section>
        </AdminLayout>
    );
}
