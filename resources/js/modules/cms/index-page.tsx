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
import { useTranslator } from '@/lib/i18n';
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
    const { locale, t, text } = useTranslator();
    const published = countValue(props.counts, 'Published');
    const activeSections = props.sections.filter(
        (section) => section.status === 'active',
    ).length;
    const visibleNavigation = flattenNavigation(props.navigationItems).filter(
        (item) => item.is_visible,
    ).length;

    return (
        <AdminLayout>
            <Head title={text('Website Control')} />

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
                        detail: t('cms.reusable_blocks', undefined, {
                            count: props.sections.length,
                        }),
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
                                <strong>
                                    {locale === 'ar'
                                        ? page.title_ar || page.title_en
                                        : page.title_en || page.title_ar}
                                </strong>
                                <span>{page.title_ar || `/${page.slug}`}</span>
                                <div className="pmc-inline-badges">
                                    {page.is_homepage ? (
                                        <StatusBadge
                                            value="homepage"
                                            label={text('Homepage')}
                                            tone="blue"
                                        />
                                    ) : null}
                                    {!page.is_visible ? (
                                        <StatusBadge
                                            value="hidden"
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
                                <span>
                                    {(locale === 'ar'
                                        ? page.excerpt_ar || page.excerpt_en
                                        : page.excerpt_en || page.excerpt_ar) ||
                                        text('No excerpt')}
                                </span>
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
                                <span>{text('Attached blocks')}</span>
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
                                        confirmMessage={t(
                                            'cms.archive_page_confirm',
                                            undefined,
                                            {
                                                title:
                                                    locale === 'ar'
                                                        ? page.title_ar ||
                                                          page.title_en ||
                                                          ''
                                                        : page.title_en ||
                                                          page.title_ar ||
                                                          '',
                                            },
                                        )}
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
                                            {text(
                                                humanLabel(
                                                    section.section_type,
                                                ),
                                            )}
                                        </span>
                                        <strong>
                                            {locale === 'ar'
                                                ? section.name_ar ||
                                                  section.name_en
                                                : section.name_en ||
                                                  section.name_ar}
                                        </strong>
                                        <small>
                                            {section.name_ar ||
                                                text('Arabic name missing')}
                                        </small>
                                    </div>
                                    <div className="pmc-cms-library-meta">
                                        <StatusBadge value={section.status} />
                                        <span>
                                            {section.page_sections_count ?? 0}{' '}
                                            {text('pages')}
                                        </span>
                                    </div>
                                    <div className="pmc-cms-card-actions">
                                        <Link
                                            className="btn btn-outline-secondary btn-sm"
                                            href={`/cms/sections/${section.id}/edit`}
                                        >
                                            <i className="bi bi-pencil" />
                                            {text('Edit copy')}
                                        </Link>
                                        {section.status !== 'archived' ? (
                                            <ArchiveAction
                                                href={`/cms/sections/${section.id}`}
                                                confirmMessage={t(
                                                    'cms.archive_section_confirm',
                                                    undefined,
                                                    {
                                                        title:
                                                            locale === 'ar'
                                                                ? section.name_ar ||
                                                                  section.name_en ||
                                                                  ''
                                                                : section.name_en ||
                                                                  section.name_ar ||
                                                                  '',
                                                    },
                                                )}
                                            />
                                        ) : null}
                                    </div>
                                </article>
                            ))
                        ) : (
                            <div className="pmc-inline-empty">
                                {t('cms.no_sections')}
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
                                {t('cms.no_navigation')}
                            </div>
                        )}
                    </div>
                </WorkspacePanel>
            </div>
        </AdminLayout>
    );
}

function NavigationCard({ item }: { item: NavigationRecord }) {
    const { locale, t, text } = useTranslator();
    const destination = item.page
        ? `/pages/${item.page.slug}`
        : item.url || '/';

    return (
        <article className="pmc-cms-navigation-card">
            <div>
                <span>{text(humanLabel(item.location))}</span>
                <strong>
                    {locale === 'ar'
                        ? item.title_ar || item.title_en
                        : item.title_en || item.title_ar}
                </strong>
                <small>{item.title_ar || destination}</small>
            </div>
            <div className="pmc-cms-navigation-meta">
                <StatusBadge
                    value={item.is_visible ? 'visible' : 'hidden'}
                    tone={item.is_visible ? 'success' : 'neutral'}
                />
                <span>{destination}</span>
                {item.children?.length ? (
                    <span>
                        {t('cms.child_links', undefined, {
                            count: item.children.length,
                        })}
                    </span>
                ) : null}
            </div>
            <div className="pmc-cms-card-actions">
                <Link
                    className="btn btn-outline-secondary btn-sm"
                    href={`/cms/navigation/${item.id}/edit`}
                >
                    <i className="bi bi-pencil" />
                    {text('Edit')}
                </Link>
                <ArchiveAction
                    href={`/navigation-items/${item.id}`}
                    label="Delete"
                    confirmMessage={t(
                        'cms.delete_navigation_confirm',
                        undefined,
                        {
                            title:
                                locale === 'ar'
                                    ? item.title_ar || item.title_en || ''
                                    : item.title_en || item.title_ar || '',
                        },
                    )}
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
