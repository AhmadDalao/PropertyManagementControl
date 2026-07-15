import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { ResourceHeader } from '@/components/resource-cycle';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

type CmsSection = {
    id: number;
    section_type: string;
    name_en: string;
    name_ar: string;
    status: string;
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

export default function CmsBuilderPage() {
    const { props } = usePage<PageProps>();
    const [draggingId, setDraggingId] = useState<number | null>(null);
    const attachForm = useForm({
        cms_section_id: String(props.sections[0]?.id ?? ''),
        sort_order: String((props.page.page_sections.length || 0) + 1),
        is_visible: true,
    });
    const orderedSections = [...props.page.page_sections].sort(
        (a, b) => a.sort_order - b.sort_order,
    );

    const attachSection = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        attachForm.post(`/cms/pages/${props.page.id}/sections`, {
            preserveScroll: true,
        });
    };

    const reorder = (targetId: number) => {
        if (!draggingId || draggingId === targetId) {
            setDraggingId(null);

            return;
        }

        const ids = orderedSections.map((item) => item.id);
        const fromIndex = ids.indexOf(draggingId);
        const toIndex = ids.indexOf(targetId);

        if (fromIndex === -1 || toIndex === -1) {
            setDraggingId(null);

            return;
        }

        ids.splice(toIndex, 0, ids.splice(fromIndex, 1)[0]);
        setDraggingId(null);

        router.put(
            `/cms/pages/${props.page.id}/sections/reorder`,
            { ordered_ids: ids },
            { preserveScroll: true },
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
                        label: 'Preview page',
                        href: props.page.is_homepage
                            ? '/'
                            : `/pages/${props.page.slug}`,
                        variant: 'secondary',
                    },
                ]}
            />

            <section className="pmc-cms-builder-layout">
                <aside className="pmc-card p-4 pmc-cms-builder-side">
                    <div className="pmc-kicker mb-2">Attach section</div>
                    <h2>Build the page</h2>
                    <p>
                        Pick a reusable section, attach it, then drag cards to
                        reorder the public page.
                    </p>

                    <form className="d-grid gap-3" onSubmit={attachSection}>
                        <label className="pmc-resource-field">
                            <span>Section</span>
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
                                    <option key={section.id} value={section.id}>
                                        {section.name_en} ·{' '}
                                        {section.section_type}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="pmc-resource-field">
                            <span>Sort order</span>
                            <input
                                className="form-control"
                                type="number"
                                value={attachForm.data.sort_order}
                                onChange={(event) =>
                                    attachForm.setData(
                                        'sort_order',
                                        event.currentTarget.value,
                                    )
                                }
                            />
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
                                <strong>Visible on page</strong>
                            </span>
                        </label>
                        <button
                            className="btn btn-primary"
                            disabled={attachForm.processing}
                        >
                            Attach section
                        </button>
                    </form>

                    <div className="pmc-cms-page-state">
                        <span>{props.page.status}</span>
                        <span>
                            {props.page.is_homepage
                                ? 'Homepage'
                                : 'Standard page'}
                        </span>
                        <span>
                            {props.page.is_visible ? 'Visible' : 'Hidden'}
                        </span>
                    </div>
                </aside>

                <main className="pmc-cms-builder-main">
                    {orderedSections.length > 0 ? (
                        orderedSections.map((item, index) => (
                            <article
                                key={item.id}
                                className={`pmc-cms-builder-section ${
                                    draggingId === item.id ? 'is-dragging' : ''
                                }`}
                                draggable
                                onDragStart={() => setDraggingId(item.id)}
                                onDragOver={(event) => event.preventDefault()}
                                onDrop={() => reorder(item.id)}
                            >
                                <div className="pmc-cms-builder-grip">
                                    <i className="bi bi-grip-vertical" />
                                    <span>{index + 1}</span>
                                </div>
                                <div className="pmc-cms-builder-copy">
                                    <div className="pmc-kicker mb-1">
                                        {item.section?.section_type ??
                                            'Section'}
                                    </div>
                                    <h2>
                                        {item.section?.name_en ??
                                            'Missing section'}
                                    </h2>
                                    <p>
                                        {item.section?.name_ar ??
                                            'No Arabic name'}
                                    </p>
                                    <div className="pmc-cms-builder-badges">
                                        <span>Sort {item.sort_order}</span>
                                        <span>
                                            {item.is_visible
                                                ? 'Visible'
                                                : 'Hidden'}
                                        </span>
                                        <span>
                                            {item.section?.status ?? 'unknown'}
                                        </span>
                                    </div>
                                </div>
                                <div className="pmc-cms-builder-actions">
                                    <button
                                        type="button"
                                        className="btn btn-light btn-sm"
                                        onClick={() =>
                                            router.put(
                                                `/cms/page-sections/${item.id}`,
                                                {
                                                    sort_order: item.sort_order,
                                                    is_visible:
                                                        !item.is_visible,
                                                    settings_json:
                                                        item.settings_json ??
                                                        {},
                                                },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        {item.is_visible ? 'Hide' : 'Show'}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-outline-danger btn-sm"
                                        onClick={() =>
                                            router.delete(
                                                `/cms/page-sections/${item.id}`,
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        Remove
                                    </button>
                                </div>
                            </article>
                        ))
                    ) : (
                        <div className="pmc-card p-4 pmc-empty-state">
                            <i className="bi bi-layout-text-window-reverse" />
                            <strong>No sections attached yet</strong>
                            <span>
                                Attach the hero section first, then add feature
                                grids, FAQ, and CTA sections.
                            </span>
                        </div>
                    )}

                    <section className="pmc-card p-4">
                        <div className="pmc-kicker mb-2">History</div>
                        <h2>Recent page activity</h2>
                        {props.timeline.length > 0 ? (
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
                        ) : (
                            <p className="pmc-empty-inline">
                                No CMS activity recorded yet.
                            </p>
                        )}
                    </section>

                    <Link href="/cms" className="btn btn-light">
                        Back to all website controls
                    </Link>
                </main>
            </section>
        </AdminLayout>
    );
}
