import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { CmsRenderer } from '@/components/cms-renderer';
import { ResourceHeader } from '@/components/resource-cycle';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
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
    const { text } = useTranslator();
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
            <Head title={`Builder · ${props.page.title_en}`} />
            <ResourceHeader
                eyebrow="CMS builder"
                title={props.page.title_en}
                description={`${props.page.title_ar} · ${props.page.status} · ${orderedSections.length} sections`}
                backHref="/cms"
                backLabel="Website control"
                actions={[
                    {
                        label: 'Edit page settings',
                        href: `/cms/pages/${props.page.id}/edit`,
                        variant: 'primary',
                    },
                    {
                        label: 'Open public preview',
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
                            ? text('Saving changes...')
                            : saveState === 'error'
                              ? text('Could not save')
                              : text('All changes saved')}
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
                                {text(
                                    panel.charAt(0).toUpperCase() +
                                        panel.slice(1),
                                )}
                            </button>
                        ),
                    )}
                </div>
                <div className="pmc-cms-builder-pills">
                    <span>{props.page.status}</span>
                    <span>
                        {props.page.is_homepage ? 'Homepage' : 'Standard page'}
                    </span>
                    <span>{visibleSections.length} visible</span>
                </div>
            </div>

            <section
                className="pmc-cms-builder-workspace"
                data-mobile-panel={mobilePanel}
            >
                <aside className="pmc-cms-library-pane">
                    <header>
                        <span>{text('Section library')}</span>
                        <h2>{text('Add content')}</h2>
                        <p>Attach a reusable bilingual section to this page.</p>
                    </header>
                    <form onSubmit={attachSection}>
                        <label
                            className="pmc-resource-field"
                            htmlFor="cms-section-library"
                        >
                            <span>{text('Sections')}</span>
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
                                        {section.name_en} ·{' '}
                                        {section.section_type}
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
                                <strong>Visible after attaching</strong>
                            </span>
                        </label>
                        <button
                            className="btn btn-primary"
                            disabled={attachForm.processing}
                        >
                            <i className="bi bi-plus-lg" />
                            {text('Attach section')}
                        </button>
                        <Link
                            href="/cms/sections/create"
                            className="btn btn-outline-secondary"
                        >
                            {text('Create new section')}
                        </Link>
                    </form>

                    <div className="pmc-cms-library-list">
                        {props.sections.slice(0, 12).map((section) => (
                            <article key={section.id}>
                                <i className="bi bi-layout-text-window" />
                                <div>
                                    <span>{section.section_type}</span>
                                    <strong>{section.name_en}</strong>
                                    <small>{section.name_ar}</small>
                                </div>
                                <em>{section.page_sections_count ?? 0} uses</em>
                            </article>
                        ))}
                    </div>
                </aside>

                <main className="pmc-cms-preview-pane">
                    <header className="pmc-cms-preview-toolbar">
                        <div>
                            <span>{text('Live canvas')}</span>
                            <strong>
                                {previewLocale === 'ar'
                                    ? props.page.title_ar
                                    : props.page.title_en}
                            </strong>
                        </div>
                        <div>
                            <div role="group" aria-label="Preview language">
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
                            <div role="group" aria-label="Preview width">
                                <button
                                    type="button"
                                    className={
                                        previewWidth === 'desktop'
                                            ? 'active'
                                            : ''
                                    }
                                    aria-label="Desktop preview"
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
                                    aria-label="Mobile preview"
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
                                    <strong>No visible sections</strong>
                                    <span>
                                        Attach or show a section to preview the
                                        page.
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                </main>

                <aside className="pmc-cms-inspector-pane">
                    <header>
                        <span>{text('Page outline')}</span>
                        <h2>{text('Sections')}</h2>
                        <p>Drag to reorder. Select one to edit its settings.</p>
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
                                    aria-label={`Drag ${item.section?.name_en ?? 'section'}`}
                                >
                                    <i className="bi bi-grip-vertical" />
                                </button>
                                <div>
                                    <span>
                                        {index + 1}.{' '}
                                        {item.section?.section_type ??
                                            'Section'}
                                    </span>
                                    <strong>
                                        {item.section?.name_en ??
                                            'Missing section'}
                                    </strong>
                                </div>
                                <span
                                    className={
                                        item.is_visible
                                            ? 'is-visible'
                                            : 'is-hidden'
                                    }
                                >
                                    {item.is_visible ? 'Visible' : 'Hidden'}
                                </span>
                            </article>
                        ))}
                    </div>

                    {selected ? (
                        <section className="pmc-cms-selection">
                            <div>
                                <span>{text('Selected section')}</span>
                                <h3>
                                    {selected.section?.name_en ??
                                        'Missing section'}
                                </h3>
                                <p>{selected.section?.name_ar}</p>
                            </div>
                            {(selectedLibraryRecord?.page_sections_count ?? 0) >
                            1 ? (
                                <div className="pmc-cms-shared-warning">
                                    <i className="bi bi-diagram-3" />
                                    Editing this reusable section changes it on{' '}
                                    {
                                        selectedLibraryRecord?.page_sections_count
                                    }{' '}
                                    pages.
                                </div>
                            ) : null}
                            <div className="pmc-cms-selection-actions">
                                {selected.section ? (
                                    <Link
                                        href={`/cms/sections/${selected.section.id}/edit`}
                                        className="btn btn-primary"
                                    >
                                        {text('Edit EN / AR content')}
                                    </Link>
                                ) : null}
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary"
                                    onClick={() => toggleVisibility(selected)}
                                >
                                    {selected.is_visible
                                        ? text('Hide section')
                                        : text('Show section')}
                                </button>
                                <div>
                                    <button
                                        type="button"
                                        className="btn btn-light"
                                        disabled={
                                            orderedSections[0]?.id ===
                                            selected.id
                                        }
                                        aria-label="Move section up"
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
                                        aria-label="Move section down"
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
                                                'Remove this section from the page?',
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
                                    {text('Remove from page')}
                                </button>
                            </div>
                        </section>
                    ) : (
                        <div className="pmc-empty-state">
                            Select a section to edit it.
                        </div>
                    )}

                    <details className="pmc-cms-history">
                        <summary>
                            <span>{text('Recent activity')}</span>
                            <strong>{props.timeline.length}</strong>
                        </summary>
                        <div className="pmc-history-timeline">
                            {props.timeline.map((event) => (
                                <div key={event.id}>
                                    <span />
                                    <strong>{event.event}</strong>
                                    <small>
                                        {event.causer ?? 'System'} ·{' '}
                                        {event.created_at ?? ''}
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
