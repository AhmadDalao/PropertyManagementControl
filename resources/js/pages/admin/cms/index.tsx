import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';

import { ArchiveAction } from '@/components/archive-action';
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
    excerpt_en?: string | null;
    excerpt_ar?: string | null;
    seo_title_en?: string | null;
    seo_title_ar?: string | null;
    seo_description_en?: string | null;
    seo_description_ar?: string | null;
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
    settings_json?: Record<string, unknown> | null;
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
    parent_id?: number | null;
    cms_page_id?: number | null;
    title_en: string;
    title_ar?: string;
    url?: string | null;
    location: string;
    target?: string | null;
    sort_order?: number | null;
    is_visible?: boolean;
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
    const [editingPage, setEditingPage] = useState<PageRecord | null>(null);
    const [editingSection, setEditingSection] = useState<SectionRecord | null>(
        null,
    );
    const [editingNavigation, setEditingNavigation] =
        useState<NavigationRecord | null>(null);

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
        content_en_json: jsonText({
            eyebrow: 'Editable section',
            headline: 'New section',
            body: 'Update this copy from the CMS.',
        }),
        content_ar_json: jsonText({
            eyebrow: 'قسم قابل للتعديل',
            headline: 'قسم جديد',
            body: 'يمكن تعديل هذا النص من إدارة الموقع.',
        }),
        status: 'active',
    });

    const attachForm = useForm({
        cms_section_id: String(props.sections[0]?.id ?? ''),
        sort_order: selectedPage?.page_sections.length ?? 0,
        is_visible: true,
    });

    const startEditingPage = (page: PageRecord) => {
        pageForm.setData({
            slug: page.slug,
            title_en: page.title_en,
            title_ar: page.title_ar ?? '',
            excerpt_en: page.excerpt_en ?? '',
            excerpt_ar: page.excerpt_ar ?? '',
            seo_title_en: page.seo_title_en ?? '',
            seo_title_ar: page.seo_title_ar ?? '',
            seo_description_en: page.seo_description_en ?? '',
            seo_description_ar: page.seo_description_ar ?? '',
            status: page.status,
            is_homepage: page.is_homepage,
            is_visible: page.is_visible,
        });
        setEditingPage(page);
        setSelectedPageId(page.id);
    };

    const clearPageForm = () => {
        setEditingPage(null);
        pageForm.reset();
    };

    const submitPageForm = () => {
        const options = {
            preserveScroll: true,
            onSuccess: clearPageForm,
        };

        if (editingPage) {
            pageForm.put(`/cms/pages/${editingPage.id}`, options);

            return;
        }

        pageForm.post('/cms/pages', options);
    };

    const startEditingSection = (section: SectionRecord) => {
        sectionForm.setData({
            section_type: section.section_type,
            name_en: section.name_en,
            name_ar: section.name_ar ?? '',
            content_en_json: jsonText(section.content_en ?? {}),
            content_ar_json: jsonText(section.content_ar ?? {}),
            status: section.status,
        });
        setEditingSection(section);
    };

    const clearSectionForm = () => {
        setEditingSection(null);
        sectionForm.reset();
    };

    const submitSectionForm = () => {
        const contentEn = parseJsonObject(sectionForm.data.content_en_json);
        const contentAr = parseJsonObject(sectionForm.data.content_ar_json);

        if (contentEn === null || contentAr === null) {
            window.alert('Section content must be valid JSON objects.');

            return;
        }

        const payload = {
            section_type: sectionForm.data.section_type,
            name_en: sectionForm.data.name_en,
            name_ar: sectionForm.data.name_ar,
            content_en: contentEn,
            content_ar: contentAr,
            status: sectionForm.data.status,
        };
        const options = {
            preserveScroll: true,
            onSuccess: clearSectionForm,
        };

        if (editingSection) {
            router.put(`/cms/sections/${editingSection.id}`, payload, options);

            return;
        }

        router.post('/cms/sections', payload, options);
    };

    const startEditingNavigation = (item: NavigationRecord) => {
        navigationForm.setData({
            parent_id: item.parent_id ? String(item.parent_id) : '',
            cms_page_id: item.cms_page_id ? String(item.cms_page_id) : '',
            location: item.location,
            title_en: item.title_en,
            title_ar: item.title_ar ?? '',
            url: item.url ?? '/',
            target: item.target ?? '_self',
            sort_order: item.sort_order ?? 0,
            is_visible: item.is_visible ?? true,
        });
        setEditingNavigation(item);
    };

    const clearNavigationForm = () => {
        setEditingNavigation(null);
        navigationForm.reset();
    };

    const submitNavigationForm = () => {
        const options = {
            preserveScroll: true,
            onSuccess: clearNavigationForm,
        };

        if (editingNavigation) {
            navigationForm.put(
                `/navigation-items/${editingNavigation.id}`,
                options,
            );

            return;
        }

        navigationForm.post('/navigation-items', options);
    };

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
                        <div className="d-flex justify-content-between gap-3 align-items-start">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    {editingPage ? 'Edit page' : 'Create page'}
                                </div>
                                <h2>
                                    {editingPage
                                        ? editingPage.title_en
                                        : 'Start a new public page'}
                                </h2>
                            </div>
                            {editingPage ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={clearPageForm}
                                >
                                    Reset
                                </button>
                            ) : null}
                        </div>
                        <form
                            className="d-grid gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                submitPageForm();
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
                            <textarea
                                className="form-control"
                                rows={2}
                                placeholder="English excerpt"
                                value={pageForm.data.excerpt_en}
                                onChange={(event) =>
                                    pageForm.setData(
                                        'excerpt_en',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <textarea
                                className="form-control"
                                rows={2}
                                placeholder="Arabic excerpt"
                                value={pageForm.data.excerpt_ar}
                                onChange={(event) =>
                                    pageForm.setData(
                                        'excerpt_ar',
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
                            <label className="pmc-toggle-card">
                                <input
                                    type="checkbox"
                                    checked={pageForm.data.is_visible}
                                    onChange={(event) =>
                                        pageForm.setData(
                                            'is_visible',
                                            event.currentTarget.checked,
                                        )
                                    }
                                />
                                <span>Visible in public site</span>
                            </label>
                            <button
                                className="btn btn-primary"
                                disabled={pageForm.processing}
                            >
                                {editingPage ? 'Update page' : 'Create page'}
                            </button>
                        </form>
                    </div>

                    <div className="pmc-cms-form-card">
                        <div className="d-flex justify-content-between gap-3 align-items-start">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Section library
                                </div>
                                <h2>
                                    {editingSection
                                        ? `Edit ${editingSection.name_en}`
                                        : 'Create reusable section'}
                                </h2>
                            </div>
                            {editingSection ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={clearSectionForm}
                                >
                                    Reset
                                </button>
                            ) : null}
                        </div>
                        <form
                            className="d-grid gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                submitSectionForm();
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
                            <label className="form-label pmc-form-label mb-0">
                                English content JSON
                            </label>
                            <textarea
                                className="form-control pmc-code-textarea"
                                rows={8}
                                value={sectionForm.data.content_en_json}
                                onChange={(event) =>
                                    sectionForm.setData(
                                        'content_en_json',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <label className="form-label pmc-form-label mb-0">
                                Arabic content JSON
                            </label>
                            <textarea
                                className="form-control pmc-code-textarea"
                                rows={8}
                                value={sectionForm.data.content_ar_json}
                                onChange={(event) =>
                                    sectionForm.setData(
                                        'content_ar_json',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <select
                                className="form-select"
                                value={sectionForm.data.status}
                                onChange={(event) =>
                                    sectionForm.setData(
                                        'status',
                                        event.currentTarget.value,
                                    )
                                }
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="archived">Archived</option>
                            </select>
                            <button
                                className="btn btn-outline-secondary"
                                disabled={sectionForm.processing}
                            >
                                {editingSection
                                    ? 'Update section'
                                    : 'Create section'}
                            </button>
                        </form>
                    </div>

                    <div className="pmc-cms-form-card">
                        <div className="d-flex justify-content-between gap-3 align-items-start">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Header menu
                                </div>
                                <h2>
                                    {editingNavigation
                                        ? `Edit ${editingNavigation.title_en}`
                                        : 'Add navigation item'}
                                </h2>
                            </div>
                            {editingNavigation ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={clearNavigationForm}
                                >
                                    Reset
                                </button>
                            ) : null}
                        </div>
                        <form
                            className="d-grid gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                submitNavigationForm();
                            }}
                        >
                            <div className="row g-2">
                                <div className="col-6">
                                    <select
                                        className="form-select"
                                        value={navigationForm.data.location}
                                        onChange={(event) =>
                                            navigationForm.setData(
                                                'location',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="header">Header</option>
                                        <option value="footer">Footer</option>
                                    </select>
                                </div>
                                <div className="col-6">
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={navigationForm.data.sort_order}
                                        onChange={(event) =>
                                            navigationForm.setData(
                                                'sort_order',
                                                Number(
                                                    event.currentTarget.value,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                            </div>
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
                            <select
                                className="form-select"
                                value={navigationForm.data.cms_page_id}
                                onChange={(event) =>
                                    navigationForm.setData(
                                        'cms_page_id',
                                        event.currentTarget.value,
                                    )
                                }
                            >
                                <option value="">Custom URL</option>
                                {props.pageOptions.map((page) => (
                                    <option key={page.id} value={page.id}>
                                        {page.title_en}
                                    </option>
                                ))}
                            </select>
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
                            <label className="pmc-toggle-card">
                                <input
                                    type="checkbox"
                                    checked={navigationForm.data.is_visible}
                                    onChange={(event) =>
                                        navigationForm.setData(
                                            'is_visible',
                                            event.currentTarget.checked,
                                        )
                                    }
                                />
                                <span>Visible in menu</span>
                            </label>
                            <button
                                className="btn btn-outline-secondary"
                                disabled={navigationForm.processing}
                            >
                                {editingNavigation
                                    ? 'Update nav item'
                                    : 'Create nav item'}
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
                                startEditingSection={startEditingSection}
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
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    className: 'text-end',
                                    render: (page) => (
                                        <div className="d-flex justify-content-end gap-2 flex-wrap">
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary btn-sm"
                                                onClick={() =>
                                                    startEditingPage(page)
                                                }
                                            >
                                                Edit
                                            </button>
                                            {page.status !== 'archived' ? (
                                                <ArchiveAction
                                                    href={`/cms/pages/${page.id}`}
                                                    confirmMessage={`Archive page ${page.title_en}? It will be hidden from the public site.`}
                                                />
                                            ) : null}
                                        </div>
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
                                actions: (
                                    <div className="d-flex gap-2 flex-wrap">
                                        <button
                                            type="button"
                                            className="btn btn-outline-secondary btn-sm"
                                            onClick={() =>
                                                startEditingSection(section)
                                            }
                                        >
                                            Edit
                                        </button>
                                        {section.status !== 'archived' ? (
                                            <ArchiveAction
                                                href={`/cms/sections/${section.id}`}
                                                confirmMessage={`Archive section ${section.name_en}? Attached pages will stop rendering it once inactive.`}
                                            />
                                        ) : null}
                                    </div>
                                ),
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
                                badge: item.is_visible
                                    ? item.location
                                    : `${item.location} hidden`,
                                actions: (
                                    <div className="d-flex gap-2 flex-wrap">
                                        <button
                                            type="button"
                                            className="btn btn-outline-secondary btn-sm"
                                            onClick={() =>
                                                startEditingNavigation(item)
                                            }
                                        >
                                            Edit
                                        </button>
                                        <ArchiveAction
                                            href={`/navigation-items/${item.id}`}
                                            label="Delete"
                                            confirmMessage={`Delete navigation item ${item.title_en}?`}
                                        />
                                    </div>
                                ),
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
    startEditingSection,
}: {
    page: BuilderPageRecord;
    draggingId: number | null;
    setDraggingId: (id: number | null) => void;
    reorderSections: (
        sourceId: number,
        targetId: number,
        page: BuilderPageRecord,
    ) => void;
    startEditingSection: (section: SectionRecord) => void;
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
                            {section ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={() => startEditingSection(section)}
                                >
                                    Edit copy
                                </button>
                            ) : null}
                            <button
                                type="button"
                                className="btn btn-outline-secondary btn-sm"
                                onClick={() =>
                                    router.put(
                                        `/cms/page-sections/${item.id}`,
                                        {
                                            sort_order: item.sort_order,
                                            is_visible: !item.is_visible,
                                        },
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                {item.is_visible ? 'Hide' : 'Show'}
                            </button>
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
        actions?: ReactNode;
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
                            <div className="pmc-compact-list-actions">
                                <em>{row.badge}</em>
                                {row.actions}
                            </div>
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

function jsonText(value: Record<string, unknown>): string {
    return JSON.stringify(value, null, 2);
}

function parseJsonObject(
    value: string,
): Record<string, FormDataConvertible> | null {
    try {
        const parsed = JSON.parse(value || '{}') as unknown;

        if (
            parsed === null ||
            Array.isArray(parsed) ||
            typeof parsed !== 'object'
        ) {
            return null;
        }

        return parsed as Record<string, FormDataConvertible>;
    } catch {
        return null;
    }
}
