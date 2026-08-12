import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/cms/workspace.css';
import '../../../css/styles/cms/section-editor.css';
import '../../../css/styles/cms/builder.css';
import '../../../css/styles/cms/responsive.css';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { CmsNavigationPanel } from './cms-navigation-panel';
import { CmsPagesTable } from './cms-pages-table';
import { CmsPublishingRail } from './cms-publishing-rail';
import { CmsSectionLibrary } from './cms-section-library';
import { CmsWorkspaceHeader } from './cms-workspace-header';
import type { CmsIndexPageProps } from './types';

export default function CmsIndexPage() {
    const { props } = usePage<CmsIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('cms.website_control')} />
            <CmsWorkspaceHeader stats={props.workspaceStats} />

            <div className="pmc-cms-overview-grid" id="cms-pages">
                <CmsPagesTable {...props} />
                <CmsPublishingRail stats={props.workspaceStats} />
            </div>

            <div className="pmc-cms-directory-grid">
                <div id="cms-sections">
                    <CmsSectionLibrary
                        sections={props.sections}
                        limitReached={props.sectionLimitReached}
                    />
                </div>
                <div id="cms-navigation">
                    <CmsNavigationPanel
                        items={props.navigationItems}
                        limitReached={props.navigationLimitReached}
                    />
                </div>
            </div>
        </AdminLayout>
    );
}
