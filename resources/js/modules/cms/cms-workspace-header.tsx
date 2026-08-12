import { Link } from '@inertiajs/react';

import { MetricGrid, WorkspaceHeader } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { CmsWorkspaceStats } from './types';

export function CmsWorkspaceHeader({ stats }: { stats: CmsWorkspaceStats }) {
    const { t } = useTranslator();

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
                        label: t('cms.create_page'),
                        href: '/cms/pages/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: t('cms.published'),
                        value: stats.published,
                        detail: t('cms.published_help'),
                        icon: 'bi-globe2',
                        tone: 'teal',
                    },
                    {
                        label: t('status.draft'),
                        value: stats.drafts,
                        detail: t('cms.page_shells'),
                        icon: 'bi-file-earmark-text',
                        tone: 'amber',
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
                    {
                        label: t('cms.missing_arabic', 'Missing Arabic'),
                        value: stats.missing_arabic,
                        detail: t(
                            'cms.missing_arabic_help',
                            'Needs translation review',
                        ),
                        icon: 'bi-translate',
                        tone: stats.missing_arabic > 0 ? 'red' : 'teal',
                    },
                ]}
            />

            <nav
                className="pmc-cms-view-switcher"
                aria-label={t('cms.workspace_views')}
            >
                <a href="#cms-pages" className="active">
                    <span>{t('cms.view_pages')}</span>
                </a>
                <a href="#cms-sections">
                    <span>{t('cms.view_sections')}</span>
                </a>
                <a href="#cms-navigation">
                    <span>{t('cms.view_navigation')}</span>
                </a>
                <Link href="/wording">
                    <span>{t('cms.publishing_translation')}</span>
                </Link>
            </nav>
        </>
    );
}
