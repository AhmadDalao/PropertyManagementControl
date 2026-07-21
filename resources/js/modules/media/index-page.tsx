import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/media.css';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { MediaMetrics } from './media-metrics';
import { MediaTable } from './media-table';
import type { MediaIndexPageProps } from './types';

export default function MediaIndexPage() {
    const { props } = usePage<MediaIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('media.workspace_title')} />
            <WorkspaceHeader
                eyebrow={t('media.workspace_eyebrow')}
                title={t('media.workspace_title')}
                description={t('media.workspace_description')}
                actions={[
                    {
                        label: t('media.upload_media'),
                        href: '/media-files/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />
            <MediaMetrics insights={props.mediaInsights} />
            <MediaTable props={props} />
        </AdminLayout>
    );
}
