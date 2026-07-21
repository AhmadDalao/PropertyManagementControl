import { Link } from '@inertiajs/react';

import { MetricGrid, WorkspaceHeader } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { CmsWorkspaceStats, CmsWorkspaceView } from './types';

export function CmsWorkspaceHeader({
    view,
    stats,
}: {
    view: CmsWorkspaceView;
    stats: CmsWorkspaceStats;
}) {
    const { t } = useTranslator();
    const createAction = {
        pages: {
            label: t('cms.create_page'),
            href: '/cms/pages/create',
        },
        sections: {
            label: t('cms.create_section'),
            href: '/cms/sections/create',
        },
        navigation: {
            label: t('cms.create_navigation'),
            href: '/cms/navigation/create',
        },
    }[view];

    return (
        <>
            <WorkspaceHeader
                eyebrow={t('cms.workspace_eyebrow')}
                title={t('cms.website_control')}
                description={t('cms.workspace_description')}
                actions={[
                    {
                        label: t('cms.preview_website'),
                        href: '/',
                        icon: 'bi-eye',
                        tone: 'secondary',
                        native: true,
                    },
                    {
                        label: t('cms.page_wording'),
                        href: '/wording',
                        icon: 'bi-translate',
                        tone: 'secondary',
                    },
                    {
                        ...createAction,
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: t('cms.pages'),
                        value: stats.pages,
                        detail: t('cms.page_shells'),
                        icon: 'bi-file-earmark-text',
                        tone: 'ink',
                    },
                    {
                        label: t('cms.published'),
                        value: stats.published,
                        detail: t('cms.published_help'),
                        icon: 'bi-globe2',
                        tone: 'teal',
                    },
                    {
                        label: t('cms.sections'),
                        value: stats.active_sections,
                        detail: t('cms.reusable_blocks', undefined, {
                            count: stats.sections,
                        }),
                        icon: 'bi-grid-1x2',
                        tone: 'blue',
                    },
                    {
                        label: t('cms.navigation'),
                        value: stats.visible_navigation,
                        detail: t('cms.navigation_visible', undefined, {
                            count: stats.navigation,
                        }),
                        icon: 'bi-signpost-split',
                        tone: 'amber',
                    },
                ]}
            />

            <nav
                className="pmc-cms-view-switcher"
                aria-label={t('cms.workspace_views')}
            >
                {(['pages', 'sections', 'navigation'] as const).map(
                    (target) => (
                        <Link
                            key={target}
                            href={`/cms?view=${target}`}
                            className={view === target ? 'active' : ''}
                            aria-current={view === target ? 'page' : undefined}
                        >
                            <i className={`bi ${viewIcon(target)}`} />
                            <span>{t(`cms.view_${target}`)}</span>
                            <strong>
                                {target === 'pages'
                                    ? stats.pages
                                    : target === 'sections'
                                      ? stats.sections
                                      : stats.navigation}
                            </strong>
                        </Link>
                    ),
                )}
            </nav>
        </>
    );
}

function viewIcon(view: CmsWorkspaceView) {
    return {
        pages: 'bi-file-earmark-text',
        sections: 'bi-grid-1x2',
        navigation: 'bi-signpost-split',
    }[view];
}
