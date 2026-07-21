import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { ContentTranslations } from './types';

export function WordingMetrics({
    totalLabels,
    customizedCount,
    content,
}: {
    totalLabels: number;
    customizedCount: number;
    content: ContentTranslations;
}) {
    const { t } = useTranslator();

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('wording.total_labels'),
                    value: totalLabels,
                    detail: t('wording.total_labels_detail'),
                    icon: 'bi-type',
                    tone: 'ink',
                },
                {
                    label: t('wording.customized'),
                    value: customizedCount,
                    detail: t('wording.customized_detail'),
                    icon: 'bi-pencil-square',
                    tone: customizedCount > 0 ? 'amber' : 'teal',
                },
                {
                    label: t('wording.content_queue_title'),
                    value: content.total,
                    detail: t('wording.content_queue_description'),
                    icon: 'bi-translate',
                    tone: content.total > 0 ? 'amber' : 'teal',
                },
            ]}
        />
    );
}
