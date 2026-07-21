import { Link } from '@inertiajs/react';

import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { OperationsDashboardProps } from '../types';

type CmsStatus = NonNullable<OperationsDashboardProps['cmsStatus']>;

export function PlatformStatusPanel({ status }: { status: CmsStatus }) {
    const { t, text } = useTranslator();

    return (
        <WorkspacePanel
            eyebrow={t('dashboard.system_overview')}
            title={t('cms.website_control')}
            description={t('cms.workspace_description')}
            action={{ label: t('cms.website_control'), href: '/cms' }}
        >
            <div className="pmc-dashboard-status-grid">
                <Link href="/cms?status=published">
                    <span>{t('cms.published')}</span>
                    <strong>{status.published}</strong>
                </Link>
                <Link href="/cms?status=draft">
                    <span>{t('status.draft')}</span>
                    <strong>{status.draft}</strong>
                </Link>
                <Link href="/cms">
                    <span>{t('cms.homepage')}</span>
                    <strong>{status.homepage ?? text('Not set')}</strong>
                </Link>
            </div>
        </WorkspacePanel>
    );
}
