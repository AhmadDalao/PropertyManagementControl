import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { dateTime } from '@/lib/utils';

import type { DailyReportIndexProps } from './types';

export function ReportMetrics({
    summary,
}: {
    summary: DailyReportIndexProps['summary'];
}) {
    const { locale, t } = useTranslator();

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('daily_reports.completed'),
                    value: summary.completed.toLocaleString(locale),
                    detail: t('daily_reports.completed_help'),
                    icon: 'bi-archive',
                    tone: 'ink',
                },
                {
                    label: t('daily_reports.active'),
                    value: summary.active.toLocaleString(locale),
                    detail: t('daily_reports.active_help'),
                    icon: 'bi-clock-history',
                    tone: summary.active > 0 ? 'amber' : 'teal',
                },
                {
                    label: t('daily_reports.archived_items'),
                    value: summary.items.toLocaleString(locale),
                    detail: t('daily_reports.archived_items_help'),
                    icon: 'bi-list-check',
                    tone: 'teal',
                },
                {
                    label: t('daily_reports.latest'),
                    value: summary.latest_completed_at
                        ? dateTime(summary.latest_completed_at, locale)
                        : t('daily_reports.never'),
                    detail: t('daily_reports.latest_help'),
                    icon: 'bi-calendar2-check',
                    tone: 'blue',
                },
            ]}
        />
    );
}
