import type { TableExportLink } from '@/components/data-table';
import type { Translator } from '@/lib/i18n';

import type { LeaseRenewalPageProps } from './types';

export function leaseRenewalDownloads(
    downloads: LeaseRenewalPageProps['downloads'],
    translate: Translator,
): TableExportLink[] {
    return [
        {
            label: translate('reports.download_pdf'),
            href: downloads.pdf,
            icon: 'bi-file-earmark-pdf',
        },
        {
            label: translate('reports.download_word'),
            href: downloads.docx,
            icon: 'bi-file-earmark-word',
        },
        {
            label: translate('actions.export_xlsx'),
            href: downloads.xlsx,
            icon: 'bi-file-earmark-excel',
        },
    ];
}
