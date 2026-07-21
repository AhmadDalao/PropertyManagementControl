import { Head, usePage } from '@inertiajs/react';

import { ResourceHeader } from '@/components/resource-cycle';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { CmsBuilderInspectorPane } from './cms-builder-inspector-pane';
import { CmsBuilderLibraryPane } from './cms-builder-library-pane';
import { CmsBuilderPreviewPane } from './cms-builder-preview-pane';
import { CmsBuilderToolbar } from './cms-builder-toolbar';
import type { CmsBuilderPageProps } from './types';
import { useCmsBuilder } from './use-cms-builder';

export default function CmsBuilderPage() {
    const { props } = usePage<CmsBuilderPageProps>();
    const { t } = useTranslator();
    const builder = useCmsBuilder(props);

    return (
        <AdminLayout>
            <Head
                title={`${t('cms.builder')} · ${builder.localizedPageTitle}`}
            />
            <ResourceHeader
                eyebrow={t('cms.builder')}
                title={builder.localizedPageTitle}
                description={t('cms.page_summary', undefined, {
                    status: t(`status.${builder.page.status}`),
                    count: builder.orderedSections.length,
                })}
                backHref="/cms"
                backLabel={t('cms.website_control')}
                actions={[
                    {
                        label: t('cms.edit_page_settings'),
                        href: `/cms/pages/${builder.page.id}/edit`,
                        variant: 'primary',
                    },
                    {
                        label: t('cms.open_public_preview'),
                        href: builder.page.is_homepage
                            ? '/'
                            : `/pages/${builder.page.slug}`,
                        variant: 'secondary',
                    },
                ]}
            />

            <CmsBuilderToolbar builder={builder} />
            <section
                className="pmc-cms-builder-workspace"
                data-mobile-panel={builder.mobilePanel}
            >
                <CmsBuilderLibraryPane builder={builder} />
                <CmsBuilderPreviewPane builder={builder} />
                <CmsBuilderInspectorPane builder={builder} />
            </section>
        </AdminLayout>
    );
}
