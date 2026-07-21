import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { formatMediaBytes } from './media-format';
import type { MediaInsights } from './types';

export function MediaMetrics({ insights }: { insights: MediaInsights }) {
    const { locale, t } = useTranslator();

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('media.total_files'),
                    value: insights.total,
                    detail: t('media.total_files_detail'),
                    icon: 'bi-images',
                    tone: 'ink',
                },
                {
                    label: t('media.public'),
                    value: insights.public,
                    detail: t('media.public_detail'),
                    icon: 'bi-globe2',
                    tone: 'teal',
                },
                {
                    label: t('media.collections'),
                    value: insights.collections,
                    detail: t('media.collections_detail'),
                    icon: 'bi-collection',
                    tone: 'blue',
                },
                {
                    label: t('media.storage_used'),
                    value: formatMediaBytes(insights.bytes, locale),
                    detail: t('media.storage_used_detail'),
                    icon: 'bi-device-ssd',
                    tone: 'amber',
                },
            ]}
        />
    );
}
