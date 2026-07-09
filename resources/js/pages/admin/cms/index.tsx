import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { DataTable, exportUrl } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type PageRecord = {
    id: number;
    title_en: string;
    title_ar?: string;
    slug: string;
    status: string;
    is_homepage: boolean;
    is_visible: boolean;
};

type SectionRecord = {
    id: number;
    name_en: string;
    name_ar?: string;
    section_type: string;
    status: string;
    content_en?: Record<string, unknown> | null;
    content_ar?: Record<string, unknown> | null;
};

type PageSectionRecord = {
    id: number;
    sort_order: number;
    is_visible: boolean;
    section?: SectionRecord | null;
};

type BuilderPageRecord = PageRecord & {
    page_sections: PageSectionRecord[];
};

type NavigationRecord = {
    id: number;
    title_en: string;
    title_ar?: string;
    url?: string | null;
    location: string;
};

type PageProps = SharedProps & {
    pages: PaginatedData<PageRecord>;
    filters: TableFilters;
    counts: TableCount[];
    pageOptions: Array<{ id: number; title_en: string }>;
    builderPages: BuilderPageRecord[];
    sections: SectionRecord[];
    navigationItems: NavigationRecord[];
};

export default function CmsPage() {
    const { props } = usePage<PageProps>();
    const [selectedPageId, setSelectedPageId] = useState(
        props.builderPages[0]?.id ?? 0,
    );
    const [draggingId, setDraggingId] = useState<number | null>(null);

    const selectedPage = useMemo(
        () =>
            props.builderPages.find((page) => page.id === selectedPageId) ??
            props.builderPages[0] ??
            null,
        [props.builderPages, selectedPageId],
    );

    const pageForm = useForm({
        slug: '',
        title_en: '',
        title_ar: '',
        excerpt_en: '',
        excerpt_ar: '',
        seo_title_en: '',
        seo_title_ar: '',
        seo_description_en: '',
        seo_description_ar: '',
        status: 'draft',
        is_homepage: false,
        is_visible: true,
    });

    const sectionForm = useForm({
        section_type: 'hero',
        name_en: '',
        name_ar: '',
        status: 'active',
    });

    const attachForm = useForm({
        cms_section_id: String(props.sections[0]?.id ?? ''),
        sort_order: selectedPage?.page_sections.length ?? 0,
        is_visible: true,
    });

    const navigationForm = useForm({
        parent_id: '',
        cms_page_id: String(props.pageOptions[0]?.id ?? ''),
        location: 'header',
        title_en: '',
        title_ar: '',
        url: '/',
        target: '_self',
        sort_order: 1,
        is_visible: true,
    });

    const reorderSections = (
        sourceId: number,
        targetId: number,
        page: BuilderPageRecord,
    ) => {
        if (sourceId === targetId) {
            return;
        }

        const current = [...page.page_sections].sort(
            (a, b) => a.sort_order - b.sort_order,
        );
        const sourceIndex = current.findIndex((item) => item.id === sourceId);
        const targetIndex = current.findIndex((item) => item.id === targetId);

        if (sourceIndex < 0 || targetIndex < 0) {
            return;
        }

        const [moved] = current.splice(sourceIndex, 1);
        current.splice(targetIndex, 0, moved);

        router.put(
            `/cms/pages/${page.id}/sections/reorder`,
            { ordered_ids: current.map((item) => item.id) },
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setDraggingId(null),
            },
        );
    };

    return (
        <AdminLayout>
            <Head title="Website Control" />
            <PageHeader
                title="Website Control"
                description="Build the public website with bilingual pages, reusable sections, drag/drop ordering, navigation, and publishing controls."
                actions={
                    <Link href="/" className="btn btn-outline-secondary">
                        <i className="bi bi-eye me-2" />
                        Preview site
                    </Link>
                }
            />

            <div className="pmc-cms-grid">
                <aside className="pmc-cms-sidebar">
                    <div className="pmc-cms-form-card">
                        <div className="pmc-kicker mb-2">Create page</div>
                        <h2>Start a new public page</h2>
                        <form
                            className="d-grid gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                pageForm.post('/cms/pages', {
                                    preserveScroll: true,
                                    onSuccess: () => pageForm.reset(),
                                });
                            }}
                        >
                            <input
                                className="form-control"
                                placeholder="English title"
                                value={pageForm.data.title_en}
                                onChange={(event) =>
                                    pageForm.setData(
                                        'title_en',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <input
                                className="form-control"
                                placeholder="Arabic title"
                                value={pageForm.data.title_ar}
                                onChange={(event) =>
                                    pageForm.setData(
                                        'title_ar',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <input
                                className="form-control"
                                placeholder="Slug, for example services"
                                value={pageForm.data.slug}
                                onChange={(event) =>
                                    pageForm.setData(
                                        'slug',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <div className="row g-2">
                                <div className="col-6">
                                    <select
                                        className="form-select"
                                        value={pageForm.data.status}
                                        onChange={(event) =>
                                            pageForm.setData(
                                                'status',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="draft">Draft</option>
                                        <option value="published">
                                            Published
                                        </option>
                                    </select>
                                </div>
                                <div className="col-6">
                                    <label className="pmc-toggle-card">
                                        <input
                                            type="checkbox"
                                            checked={pageForm.data.is_homepage}
                                            onChange={(event) =>
                                                pageForm.setData(
                                                    'is_homepage',
                                                    event.currentTarget.checked,
                                                )
                                            }
                                        />
                                        <span>Homepage</span>
                                    </label>
                                </div>
                            </div>
                            <button
                                className="btn btn-primary"
                                disabled={pageForm.processing}
                            >
                                Create page
                            </button>
                        </form>
                    </div>

                    <div className="pmc-cms-form-card">
                        <div className="pmc-kicker mb-2">Section library</div>
                        <h2>Create reusable section</h2>
                        <form
                            className="d-grid gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                sectionForm.transform((data) => ({
                                    ...data,
                                    content_en: {
                                        eyebrow: 'Editable section',
                                        headline: data.name_en,
                                        body: 'Update this copy from the CMS.',
                                    },
                                    content_ar: {
                                        eyebrow: 'قسم قابل للتعديل',
                                        headline: data.name_ar,
                                        body: 'يمكن تعديل هذا النص من إدارة الموقع.',
                                    },
                                }));
                                sectionForm.post('/cms/sections', {
                                    preserveScroll: true,
                                    onSuccess: () => sectionForm.reset(),
                                });
                            }}
                        >
                            <select
                                className="form-select"
                                value={sectionForm.data.section_type}
                                onChange={(event) =>
                                    sectionForm.setData(
                                        'section_type',
                                        event.currentTarget.value,
                                    )
                                }
                            >
                                <option value="hero">Hero</option>
                                <option value="role_cards">Role cards</option>
                                <option value="workflow">Workflow</option>
                                <option value="dashboard_preview">
                                    Dashboard preview
                                </option>
                                <option value="feature_grid">
                                    Feature grid
                                </option>
                                <option value="operations_strip">
                                    Operations strip
                                </option>
                                <option value="faq">FAQ</option>
                                <option value="final_cta">Final CTA</option>
                                <option value="content">Content</option>
                            </select>
                            <input
                                className="form-control"
                                placeholder="English section name"
                                value={sectionForm.data.name_en}
                                onChange={(event) =>
                                    sectionForm.setData(
                                        'name_en',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <input
                                className="form-control"
                                placeholder="Arabic section name"
                                value={sectionForm.data.name_ar}
                                onChange={(event) =>
                                    sectionForm.setData(
                                        'name_ar',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <button
                                className="btn btn-outline-secondary"
                                disabled={sectionForm.processing}
                            >
                                Create section
                            </button>
                        </form>
                    </div>

                    <div className="pmc-cms-form-card">
                        <div className="pmc-kicker mb-2">Header menu</div>
                        <h2>Add navigation item</h2>
                        <form
                            className="d-grid gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                navigationForm.post('/navigation-items', {
                                    preserveScroll: true,
                                    onSuccess: () => navigationForm.reset(),
                                });
                            }}
                        >
                            <input
                                className="form-control"
                                placeholder="English label"
                                value={navigationForm.data.title_en}
                                onChange={(event) =>
                                    navigationForm.setData(
                                        'title_en',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <input
                                className="form-control"
                                placeholder="Arabic label"
                                value={navigationForm.data.title_ar}
                                onChange={(event) =>
                                    navigationForm.setData(
                                        'title_ar',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <input
                                className="form-control"
                                placeholder="URL or anchor"
                                value={navigationForm.data.url}
                                onChange={(event) =>
                                    navigationForm.setData(
                                        'url',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <button
                                className="btn btn-outline-secondary"
                                disabled={navigationForm.processing}
                            >
                                Create nav item
                            </button>
                        </form>
                    </div>
                </aside>

                <main className="pmc-cms-main">
                    <section className="pmc-builder-card">
                        <div className="pmc-builder-head">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Visual builder
                                </div>
                                <h2>Drag sections into the right order</h2>
                                <p>
                                    Choose a page, attach predefined sections,
                                    drag to reorder, then refresh or preview to
                                    verify the published layout.
                                </p>
                            </div>
                            {selectedPage ? (
                                <Link
                                    href={
                                        selectedPage.is_homepage
                                            ? '/'
                                            : `/pages/${selectedPage.slug}`
                                    }
                                    className="btn btn-primary"
                                >
                                    <i className="bi bi-box-arrow-up-right me-2" />
                                    Preview
                                </Link>
                            ) : null}
                        </div>

                        <div className="pmc-builder-toolbar">
                            <label>
                                <span>Page</span>
                                <select
                                    className="form-select"
                                    value={selectedPage?.id ?? ''}
                                    onChange={(event) =>
                                        setSelectedPageId(
                                            Number(event.currentTarget.value),
                                        )
                                    }
                                >
                                    {props.builderPages.map((page) => (
                                        <option key={page.id} value={page.id}>
                                            {page.title_en}
                                            {page.is_homepage
                                                ? ' - Homepage'
                                                : ''}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            {selectedPage ? (
                                <form
                                    className="pmc-builder-attach"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        attachForm.post(
                                            `/cms/pages/${selectedPage.id}/sections`,
                                            { preserveScroll: true },
                                        );
                                    }}
                                >
                                    <select
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
                                            <option
                                                key={section.id}
                                                value={section.id}
                                            >
                                                {section.name_en} -{' '}
                                                {section.section_type}
                                            </option>
                                        ))}
                                    </select>
                                    <button
                                        className="btn btn-outline-secondary"
                                        disabled={attachForm.processing}
                                    >
                                        <i className="bi bi-plus-lg me-2" />
                                        Add section
                                    </button>
                                </form>
                            ) : null}
                        </div>

                        {selectedPage ? (
                            <BuilderCanvas
                                page={selectedPage}
                                draggingId={draggingId}
                                setDraggingId={setDraggingId}
                                reorderSections={reorderSections}
                            />
                        ) : (
                            <div className="pmc-empty-state">
                                <i className="bi bi-layout-text-window" />
                                <strong>No pages yet</strong>
                                <span>
                                    Create a page first, then attach sections to
                                    build it.
                                </span>
                            </div>
                        )}
                    </section>

                    <section className="pmc-card p-4">
                        <DataTable
                            title="CMS pages"
                            description="Search page titles, slugs, and excerpts."
                            data={props.pages}
                            filters={props.filters}
                            counts={props.counts}
                            basePath="/cms"
                            exportHref={exportUrl(
                                '/exports/cms-pages',
                                props.filters,
                            )}
                            filterFields={[
                                {
                                    name: 'status',
                                    label: 'Status',
                                    options: [
                                        { label: 'All', value: 'all' },
                                        { label: 'Draft', value: 'draft' },
                                        {
                                            label: 'Published',
                                            value: 'published',
                                        },
                                        {
                                            label: 'Archived',
                                            value: 'archived',
                                        },
                                    ],
                                },
                            ]}
                            columns={[
                                {
                                    key: 'title',
                                    label: 'Title',
                                    render: (page) => (
                                        <>
                                            <div className="fw-semibold">
                                                {page.title_en}
                                            </div>
                                            <div className="small text-secondary">
                                                {page.title_ar}
                                            </div>
                                            {page.is_homepage ? (
                                                <span className="pmc-chip pmc-chip--primary mt-2">
                                                    Homepage
                                                </span>
                                            ) : null}
                                        </>
                                    ),
                                },
                                {
                                    key: 'slug',
                                    label: 'Slug',
                                    render: (page) => page.slug,
                                },
                                {
                                    key: 'visibility',
                                    label: 'Visibility',
                                    render: (page) =>
                                        page.is_visible ? 'Visible' : 'Hidden',
                                },
                                {
                                    key: 'status',
                                    label: 'Status',
                                    render: (page) => (
                                        <span className="pmc-chip pmc-chip--teal">
                                            {page.status}
                                        </span>
                                    ),
                                },
                            ]}
                        />
                    </section>

                    <div className="pmc-cms-lists">
                        <ListPanel
                            title="Section library"
                            empty="No sections yet."
                            rows={props.sections.map((section) => ({
                                id: section.id,
                                title: section.name_en,
                                subtitle: section.name_ar ?? '',
                                meta: section.section_type,
                                badge: section.status,
                            }))}
                        />
                        <ListPanel
                            title="Navigation"
                            empty="No navigation items yet."
                            rows={props.navigationItems.map((item) => ({
                                id: item.id,
                                title: item.title_en,
                                subtitle: item.title_ar ?? '',
                                meta: item.url ?? '-',
                                badge: item.location,
                            }))}
                        />
                    </div>
                </main>
            </div>
        </AdminLayout>
    );
}

function BuilderCanvas({
    page,
    draggingId,
    setDraggingId,
    reorderSections,
}: {
    page: BuilderPageRecord;
    draggingId: number | null;
    setDraggingId: (id: number | null) => void;
    reorderSections: (
        sourceId: number,
        targetId: number,
        page: BuilderPageRecord,
    ) => void;
}) {
    const sections = [...page.page_sections].sort(
        (a, b) => a.sort_order - b.sort_order,
    );

    if (sections.length === 0) {
        return (
            <div className="pmc-empty-state">
                <i className="bi bi-plus-square" />
                <strong>This page has no sections</strong>
                <span>
                    Pick a section from the library above and add it to the
                    page.
                </span>
            </div>
        );
    }

    return (
        <div className="pmc-builder-canvas">
            {sections.map((item, index) => {
                const section = item.section;
                const content = section?.content_en ?? {};
                const headline =
                    stringValue(content.headline) ||
                    stringValue(content.title) ||
                    section?.name_en ||
                    'Untitled section';

                return (
                    <article
                        key={item.id}
                        className={`pmc-builder-section ${
                            draggingId === item.id ? 'is-dragging' : ''
                        }`}
                        draggable
                        onDragStart={() => setDraggingId(item.id)}
                        onDragOver={(event) => event.preventDefault()}
                        onDrop={() => {
                            if (draggingId !== null) {
                                reorderSections(draggingId, item.id, page);
                            }
                        }}
                        onDragEnd={() => setDraggingId(null)}
                    >
                        <div className="pmc-builder-handle">
                            <i className="bi bi-grip-vertical" />
                            <span>{index + 1}</span>
                        </div>
                        <div className="pmc-builder-section-copy">
                            <strong>{headline}</strong>
                            <span>
                                {section?.section_type ?? 'section'} ·{' '}
                                {item.is_visible ? 'Visible' : 'Hidden'}
                            </span>
                        </div>
                        <div className="pmc-builder-section-actions">
                            <span className="pmc-chip pmc-chip--teal">
                                {section?.status ?? 'missing'}
                            </span>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    router.delete(
                                        `/cms/page-sections/${item.id}`,
                                        { preserveScroll: true },
                                    );
                                }}
                            >
                                <button
                                    type="submit"
                                    className="btn btn-outline-danger btn-sm"
                                >
                                    Remove
                                </button>
                            </form>
                        </div>
                    </article>
                );
            })}
        </div>
    );
}

function ListPanel({
    title,
    empty,
    rows,
}: {
    title: string;
    empty: string;
    rows: Array<{
        id: number;
        title: string;
        subtitle: string;
        meta: string;
        badge: string;
    }>;
}) {
    return (
        <section className="pmc-card p-4">
            <div className="pmc-kicker mb-3">{title}</div>
            <div className="pmc-compact-list">
                {rows.length > 0 ? (
                    rows.map((row) => (
                        <div key={row.id}>
                            <div>
                                <strong>{row.title}</strong>
                                <span>{row.subtitle || row.meta}</span>
                            </div>
                            <em>{row.badge}</em>
                        </div>
                    ))
                ) : (
                    <div className="pmc-inline-empty">{empty}</div>
                )}
            </div>
        </section>
    );
}

function stringValue(value: unknown): string {
    return typeof value === 'string' ? value : '';
}
