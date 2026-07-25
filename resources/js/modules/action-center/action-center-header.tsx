import { WorkspaceHeader } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { actionCenterExportUrl } from './action-center-query';
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
                    label: t('action_center.export_xlsx'),
                    href: actionCenterExportUrl(filters),
                    icon: 'bi-file-earmark-excel',
                    tone: 'primary',
                    native: true,
                },
            ]}
        />
    );
}
