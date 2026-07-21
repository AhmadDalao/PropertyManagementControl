import { Head, usePage } from '@inertiajs/react';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { CmsNavigationPanel } from './cms-navigation-panel';
import { CmsPagesTable } from './cms-pages-table';
import { CmsSectionLibrary } from './cms-section-library';
import { CmsWorkspaceHeader } from './cms-workspace-header';
import type { CmsIndexPageProps } from './types';

export default function CmsIndexPage() {
    const { props } = usePage<CmsIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('cms.website_control')} />
            <CmsWorkspaceHeader
                view={props.view}
                stats={props.workspaceStats}
            />

            {props.view === 'pages' ? <CmsPagesTable {...props} /> : null}
            {props.view === 'sections' ? (
                <CmsSectionLibrary
                    sections={props.sections}
                    limitReached={props.sectionLimitReached}
                />
            ) : null}
            {props.view === 'navigation' ? (
                <CmsNavigationPanel
                    items={props.navigationItems}
                    limitReached={props.navigationLimitReached}
                />
            ) : null}
        </AdminLayout>
    );
}
