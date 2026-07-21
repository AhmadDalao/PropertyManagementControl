import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { AuditInsights } from './types';

export function AuditMetrics({ insights }: { insights: AuditInsights }) {
    const { t } = useTranslator();

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('audit.total_events'),
                    value: insights.total,
                    detail: t('audit.total_events_detail'),
                    icon: 'bi-clock-history',
                    tone: 'ink',
                },
                {
                    label: t('audit.created_events'),
                    value: insights.created,
                    detail: t('audit.created_events_detail'),
                    icon: 'bi-plus-circle',
                    tone: 'teal',
                },
                {
                    label: t('audit.updated_events'),
                    value: insights.updated,
                    detail: t('audit.updated_events_detail'),
                    icon: 'bi-pencil-square',
                    tone: 'blue',
                },
                {
                    label: t('audit.deleted_events'),
                    value: insights.deleted,
                    detail: t('audit.deleted_events_detail'),
                    icon: 'bi-archive',
                    tone: 'red',
                },
            ]}
        />
    );
}
