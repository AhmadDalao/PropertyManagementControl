import { Head, Link, usePage } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import {
    MetricGrid,
    RecordActions,
    StatusBadge,
    WorkspaceHeader,
    WorkspacePanel,
    humanLabel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

import type {
    CmsPageRecord,
    CmsSectionRecord,
    NavigationRecord,
} from './types';

type PageProps = SharedProps & {
    pages: PaginatedData<CmsPageRecord>;
    filters: TableFilters;
    counts: TableCount[];
    sections: CmsSectionRecord[];
    navigationItems: NavigationRecord[];
};

export default function CmsIndexPage() {
    const { props } = usePage<PageProps>();
    const published = countValue(props.counts, 'Published');
    const activeSections = props.sections.filter(
        (section) => section.status === 'active',
    ).length;
    const visibleNavigation = flattenNavigation(props.navigationItems).filter(
        (item) => item.is_visible,
    ).length;

    return (
        <AdminLayout>
            <Head title="Website Control" />

            <WorkspaceHeader
                eyebrow="System"
                title="Website control"
                description="Manage public pages, reusable bilingual sections, and navigation without mixing every form into one screen."
                actions={[
                    {
                        label: 'Preview website',
                        href: '/',
                        icon: 'bi-eye',
                        tone: 'secondary',
                        native: true,
                    },
                    {
                        label: 'Page wording',
                        href: '/wording',
                        icon: 'bi-translate',
                        tone: 'secondary',
                    },
                    {
                        label: 'Create page',
                        href: '/cms/pages/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Pages',
                        value: props.pages.total,
                        detail: 'Public page shells',
                        icon: 'bi-file-earmark-text',
                        tone: 'ink',
                    },
                    {
                        label: 'Published',
                        value: published,
                        detail: 'Available on the public site',
                        icon: 'bi-globe2',
                        tone: 'teal',
                    },
                    {
                        label: 'Sections',
                        value: activeSections,
                        detail: `${props.sections.length} total reusable blocks`,
                        icon: 'bi-grid-1x2',
                        tone: 'blue',
                    },
                    {
                        label: 'Navigation',
                        value: visibleNavigation,
                        detail: 'Visible header and footer links',
                        icon: 'bi-signpost-split',
                        tone: 'amber',
                    },
                ]}
            />

            <DataTable
                title="Public pages"
                description="Find a page, open its visual builder, or edit its bilingual settings."
                data={props.pages}
                filters={props.filters}
                counts={props.counts}
                basePath="/cms"
                createHref="/cms/pages/create"
                createLabel="Create page"
                rowHref={(page) => `/cms/pages/${page.id}`}
                exportHref={exportUrl('/exports/cms-pages', props.filters)}
                filterFields={[
                    {
                        name: 'status',
                        label: 'Status',
                        options: [
                            { label: 'All', value: 'all' },
                            { label: 'Draft', value: 'draft' },
                            { label: 'Published', value: 'published' },
                            { label: 'Archived', value: 'archived' },
                        ],
                    },
                ]}
                columns={[
                    {
                        key: 'title',
                        label: 'Page',
                        render: (page) => (
                            <div className="pmc-primary-cell">
                                <strong>{page.title_en}</strong>
                                <span>{page.title_ar || `/${page.slug}`}</span>
                                <div className="pmc-inline-badges">
                                    {page.is_homepage ? (
                                        <StatusBadge
                                            value="Homepage"
                                            tone="blue"
                                        />
                                    ) : null}
                                    {!page.is_visible ? (
                                        <StatusBadge
                                            value="Hidden"
                                            tone="neutral"
                                        />
                                    ) : null}
                                </div>
                            </div>
                        ),
                    },
                    {
                        key: 'slug',
                        label: 'Public path',
                        render: (page) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {page.is_homepage
                                        ? '/'
                                        : `/pages/${page.slug}`}
                                </strong>
                                <span>{page.excerpt_en || 'No excerpt'}</span>
                            </div>
                        ),
                    },
                    {
                        key: 'sections',
                        label: 'Sections',
                        render: (page) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {page.page_sections?.length ?? 0}
                                </strong>
                                <span>Attached blocks</span>
                            </div>
                        ),
                    },
                    {
                        key: 'status',
                        label: 'Status',
                        render: (page) => <StatusBadge value={page.status} />,
                    },
                    {
                        key: 'actions',
                        label: 'Actions',
                        className: 'text-end',
                        render: (page) => (
                            <RecordActions
                                showHref={`/cms/pages/${page.id}`}
                                editHref={`/cms/pages/${page.id}/edit`}
                            >
                                {page.status !== 'archived' ? (
                                    <ArchiveAction
                                        href={`/cms/pages/${page.id}`}
                                        confirmMessage={`Archive ${page.title_en}? It will be removed from the public site.`}
                                    />
                                ) : null}
                            </RecordActions>
                        ),
                    },
                ]}
            />

            <div className="pmc-cms-overview-grid">
                <WorkspacePanel
                    eyebrow="Reusable blocks"
                    title="Section library"
                    description="Edit copy once, then attach the section to any page."
                    action={{
                        label: 'Create section',
                        href: '/cms/sections/create',
                    }}
                >
                    <div className="pmc-cms-library-grid">
                        {props.sections.length > 0 ? (
                            props.sections.map((section) => (
                                <article
                                    className="pmc-cms-library-card"
                                    key={section.id}
                                >
                                    <div className="pmc-cms-library-icon">
                                        <i
                                            className={`bi ${sectionIcon(section.section_type)}`}
                                        />
                                    </div>
                                    <div className="pmc-cms-library-copy">
                                        <span>
                                            {humanLabel(section.section_type)}
                                        </span>
                                        <strong>{section.name_en}</strong>
                                        <small>
                                            {section.name_ar ||
                                                'Arabic name missing'}
                                        </small>
                                    </div>
                                    <div className="pmc-cms-library-meta">
                                        <StatusBadge value={section.status} />
                                        <span>
                                            {section.page_sections_count ?? 0}{' '}
                                            pages
                                        </span>
                                    </div>
                                    <div className="pmc-cms-card-actions">
                                        <Link
                                            className="btn btn-outline-secondary btn-sm"
                                            href={`/cms/sections/${section.id}/edit`}
                                        >
                                            <i className="bi bi-pencil" />
                                            Edit copy
                                        </Link>
                                        {section.status !== 'archived' ? (
                                            <ArchiveAction
                                                href={`/cms/sections/${section.id}`}
                                                confirmMessage={`Archive ${section.name_en}? Inactive sections stop rendering on public pages.`}
                                            />
                                        ) : null}
                                    </div>
                                </article>
                            ))
                        ) : (
                            <div className="pmc-inline-empty">
                                No reusable sections yet. Create the hero first.
                            </div>
                        )}
                    </div>
                </WorkspacePanel>

                <WorkspacePanel
                    eyebrow="Public menus"
                    title="Navigation"
                    description="Header and footer links stay separate from page content."
                    action={{
                        label: 'Create link',
                        href: '/cms/navigation/create',
                    }}
                >
                    <div className="pmc-cms-navigation-list">
                        {props.navigationItems.length > 0 ? (
                            props.navigationItems.map((item) => (
                                <NavigationCard key={item.id} item={item} />
                            ))
                        ) : (
                            <div className="pmc-inline-empty">
                                No navigation links yet.
                            </div>
                        )}
                    </div>
                </WorkspacePanel>
            </div>
        </AdminLayout>
    );
}

function NavigationCard({ item }: { item: NavigationRecord }) {
    const destination = item.page
        ? `/pages/${item.page.slug}`
        : item.url || '/';

    return (
        <article className="pmc-cms-navigation-card">
            <div>
                <span>{humanLabel(item.location)}</span>
                <strong>{item.title_en}</strong>
                <small>{item.title_ar || destination}</small>
            </div>
            <div className="pmc-cms-navigation-meta">
                <StatusBadge
                    value={item.is_visible ? 'Visible' : 'Hidden'}
                    tone={item.is_visible ? 'success' : 'neutral'}
                />
                <span>{destination}</span>
                {item.children?.length ? (
                    <span>{item.children.length} child links</span>
                ) : null}
            </div>
            <div className="pmc-cms-card-actions">
                <Link
                    className="btn btn-outline-secondary btn-sm"
                    href={`/cms/navigation/${item.id}/edit`}
                >
                    <i className="bi bi-pencil" />
                    Edit
                </Link>
                <ArchiveAction
                    href={`/navigation-items/${item.id}`}
                    label="Delete"
                    confirmMessage={`Delete navigation item ${item.title_en}?`}
                />
            </div>
        </article>
    );
}

function countValue(counts: TableCount[], label: string) {
    return (
        counts.find(
            (count) => count.label.toLowerCase() === label.toLowerCase(),
        )?.value ?? 0
    );
}

function flattenNavigation(items: NavigationRecord[]): NavigationRecord[] {
    return items.flatMap((item) => [
        item,
        ...flattenNavigation(item.children ?? []),
    ]);
}

function sectionIcon(type: string) {
    const icons: Record<string, string> = {
        hero: 'bi-window-fullscreen',
        role_cards: 'bi-people',
        workflow: 'bi-diagram-3',
        dashboard_preview: 'bi-speedometer2',
        feature_grid: 'bi-grid',
        operations_strip: 'bi-activity',
        faq: 'bi-question-circle',
        final_cta: 'bi-megaphone',
        metrics: 'bi-bar-chart',
    };

    return icons[type] ?? 'bi-layout-text-window';
}
