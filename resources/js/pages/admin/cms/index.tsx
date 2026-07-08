import { Head, useForm, usePage } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

type PageRecord = {
    id: number;
    title_en: string;
    slug: string;
    status: string;
    is_homepage: boolean;
};

type SectionRecord = {
    id: number;
    name_en: string;
    section_type: string;
    status: string;
};

type NavigationRecord = {
    id: number;
    title_en: string;
    url?: string | null;
    location: string;
};

type PageProps = SharedProps & {
    pages: PageRecord[];
    sections: SectionRecord[];
    navigationItems: NavigationRecord[];
};

export default function CmsPage() {
    const { props } = usePage<PageProps>();

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

    const navigationForm = useForm({
        parent_id: '',
        cms_page_id: String(props.pages[0]?.id ?? ''),
        location: 'header',
        title_en: '',
        title_ar: '',
        url: '/',
        target: '_self',
        sort_order: 1,
        is_visible: true,
    });

    return (
        <AdminLayout>
            <Head title="Website Control" />
            <PageHeader
                title="Website Control"
                description="Manage pages, predefined sections, navigation, and homepage visibility."
            />

            <div className="row g-4">
                <div className="col-lg-4">
                    <div className="pmc-card p-4 mb-4">
                        <div className="pmc-kicker mb-2">Create page</div>
                        <form
                            className="d-grid gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                pageForm.post('/cms/pages', { preserveScroll: true });
                            }}
                        >
                            <input
                                className="form-control"
                                placeholder="English title"
                                value={pageForm.data.title_en}
                                onChange={(event) => pageForm.setData('title_en', event.currentTarget.value)}
                            />
                            <input
                                className="form-control"
                                placeholder="Arabic title"
                                value={pageForm.data.title_ar}
                                onChange={(event) => pageForm.setData('title_ar', event.currentTarget.value)}
                            />
                            <input
                                className="form-control"
                                placeholder="Slug"
                                value={pageForm.data.slug}
                                onChange={(event) => pageForm.setData('slug', event.currentTarget.value)}
                            />
                            <button className="btn btn-primary" disabled={pageForm.processing}>
                                Create page
                            </button>
                        </form>
                    </div>

                    <div className="pmc-card p-4 mb-4">
                        <div className="pmc-kicker mb-2">Create section</div>
                        <form
                            className="d-grid gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                sectionForm.transform((data) => ({
                                    ...data,
                                    content_en: {
                                        headline: data.name_en,
                                        subheadline: 'Editable section content',
                                    },
                                    content_ar: {
                                        headline: data.name_ar,
                                        subheadline: 'محتوى قابل للتعديل',
                                    },
                                }));
                                sectionForm.post('/cms/sections', { preserveScroll: true });
                            }}
                        >
                            <select
                                className="form-select"
                                value={sectionForm.data.section_type}
                                onChange={(event) =>
                                    sectionForm.setData('section_type', event.currentTarget.value)
                                }
                            >
                                <option value="hero">Hero</option>
                                <option value="metrics">Metrics</option>
                                <option value="content">Content</option>
                            </select>
                            <input
                                className="form-control"
                                placeholder="English section name"
                                value={sectionForm.data.name_en}
                                onChange={(event) => sectionForm.setData('name_en', event.currentTarget.value)}
                            />
                            <input
                                className="form-control"
                                placeholder="Arabic section name"
                                value={sectionForm.data.name_ar}
                                onChange={(event) => sectionForm.setData('name_ar', event.currentTarget.value)}
                            />
                            <button className="btn btn-outline-secondary" disabled={sectionForm.processing}>
                                Create section
                            </button>
                        </form>
                    </div>

                    <div className="pmc-card p-4">
                        <div className="pmc-kicker mb-2">Navigation item</div>
                        <form
                            className="d-grid gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                navigationForm.post('/navigation-items', { preserveScroll: true });
                            }}
                        >
                            <input
                                className="form-control"
                                placeholder="English label"
                                value={navigationForm.data.title_en}
                                onChange={(event) =>
                                    navigationForm.setData('title_en', event.currentTarget.value)
                                }
                            />
                            <input
                                className="form-control"
                                placeholder="Arabic label"
                                value={navigationForm.data.title_ar}
                                onChange={(event) =>
                                    navigationForm.setData('title_ar', event.currentTarget.value)
                                }
                            />
                            <input
                                className="form-control"
                                placeholder="URL"
                                value={navigationForm.data.url}
                                onChange={(event) => navigationForm.setData('url', event.currentTarget.value)}
                            />
                            <button className="btn btn-outline-secondary" disabled={navigationForm.processing}>
                                Create nav item
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-lg-8">
                    <div className="pmc-card p-4 mb-4">
                        <div className="pmc-kicker mb-3">Pages</div>
                        <div className="table-responsive">
                            <table className="table pmc-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Slug</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.pages.map((page) => (
                                        <tr key={page.id}>
                                            <td>
                                                <div className="fw-semibold">{page.title_en}</div>
                                                {page.is_homepage ? (
                                                    <span className="pmc-chip pmc-chip--primary mt-2">Homepage</span>
                                                ) : null}
                                            </td>
                                            <td>{page.slug}</td>
                                            <td>{page.status}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="pmc-card p-4 mb-4">
                        <div className="pmc-kicker mb-3">Sections</div>
                        <div className="table-responsive">
                            <table className="table pmc-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.sections.map((section) => (
                                        <tr key={section.id}>
                                            <td>{section.name_en}</td>
                                            <td>{section.section_type}</td>
                                            <td>{section.status}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="pmc-card p-4">
                        <div className="pmc-kicker mb-3">Navigation</div>
                        <div className="table-responsive">
                            <table className="table pmc-table">
                                <thead>
                                    <tr>
                                        <th>Label</th>
                                        <th>URL</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.navigationItems.map((item) => (
                                        <tr key={item.id}>
                                            <td>{item.title_en}</td>
                                            <td>{item.url ?? '-'}</td>
                                            <td>{item.location}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
