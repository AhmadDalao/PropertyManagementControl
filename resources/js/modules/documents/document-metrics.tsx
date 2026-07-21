import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { DocumentIndexPageProps } from './types';

type DocumentMetricsProps = Pick<DocumentIndexPageProps, 'documentInsights'>;

export function DocumentMetrics({ documentInsights }: DocumentMetricsProps) {
    const { t } = useTranslator();

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('documents.title'),
                    value: documentInsights.total,
                    detail: t('documents.metric_files_detail'),
                    icon: 'bi-folder2-open',
                    tone: 'ink',
                },
                {
                    label: t('documents.contracts'),
                    value: documentInsights.contracts,
                    detail: t('documents.signed_contracts', undefined, {
                        count: documentInsights.signed,
                    }),
                    icon: 'bi-file-earmark-text',
                    tone: 'blue',
                },
                {
                    label: t('documents.receipts'),
                    value: documentInsights.receipts,
                    detail: t('documents.payment_proof_pdfs'),
                    icon: 'bi-receipt',
                    tone: 'teal',
                },
                {
                    label: t('documents.portal_visible'),
                    value: documentInsights.portal_visible,
                    detail: t('documents.portal_visible_detail'),
                    icon: 'bi-eye',
                    tone: 'amber',
                },
            ]}
        />
    );
}
