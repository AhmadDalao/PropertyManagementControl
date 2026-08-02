import type { WorkspaceDownload } from '@/components/operations';
import type { Translator } from '@/lib/i18n';

import { actionCenterReportUrl } from './action-center-query';
import type { ActionCenterFilters } from './types';

export function actionCenterDownloads(
    filters: ActionCenterFilters,
    translate: Translator,
): WorkspaceDownload[] {
    return [
        {
            label: translate('reports.download_pdf'),
            href: actionCenterReportUrl(filters, 'pdf'),
            icon: 'bi-file-earmark-pdf',
        },
        {
            label: translate('reports.download_word'),
            href: actionCenterReportUrl(filters, 'docx'),
            icon: 'bi-file-earmark-word',
        },
        {
            label: translate('actions.export_xlsx'),
            href: actionCenterReportUrl(filters, 'xlsx'),
            icon: 'bi-file-earmark-excel',
        },
    ];
}
