import { WorkspaceHeader } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { actionCenterDownloads } from './action-center-downloads';
import type { ActionCenterFilters } from './types';

export function ActionCenterHeader({
    filters,
}: {
    filters: ActionCenterFilters;
}) {
    const { t } = useTranslator();

    return (
        <WorkspaceHeader
            eyebrow={t('action_center.eyebrow')}
            title={t('action_center.title')}
            description={t('action_center.description')}
            actions={[
                {
                    label: t('action_center.open_reports'),
                    href: '/reports',
                    icon: 'bi-graph-up-arrow',
                    tone: 'quiet',
                },
                {
                    label: t('action_center.download_brief'),
                    downloads: actionCenterDownloads(filters, t),
                    icon: 'bi-download',
                    tone: 'primary',
                },
            ]}
        />
    );
}
