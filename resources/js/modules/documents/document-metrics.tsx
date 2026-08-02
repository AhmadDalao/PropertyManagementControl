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
                    detail: t('documents.no_expiry_count', undefined, {
                        count: documentInsights.no_expiry,
                    }),
                    icon: 'bi-folder2-open',
                    tone: 'ink',
                },
                {
                    label: t('documents.expiry_expired'),
                    value: documentInsights.expired,
                    detail: t('documents.expired_detail'),
                    icon: 'bi-exclamation-octagon',
                    tone: 'red',
                },
                {
                    label: t('documents.expiring_90'),
                    value: documentInsights.expiring_90,
                    detail: t('documents.expiring_90_detail'),
                    icon: 'bi-calendar2-week',
                    tone: 'amber',
                },
                {
                    label: t('documents.portal_visible'),
                    value: documentInsights.portal_visible,
                    detail: t('documents.portal_visible_detail'),
                    icon: 'bi-eye',
                    tone: 'teal',
                },
            ]}
        />
    );
}
