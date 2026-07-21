import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import type { MaintenanceIndexPageProps } from './types';

type MaintenanceMetricsProps = Pick<
    MaintenanceIndexPageProps,
    'maintenanceInsights' | 'mode' | 'app'
>;

export function MaintenanceMetrics({
    maintenanceInsights: insights,
    mode,
    app,
}: MaintenanceMetricsProps) {
    const { t } = useTranslator();
    const activeCount = insights.open + insights.in_progress;

    return (
        <MetricGrid
            metrics={[
                {
                    label: 'Active requests',
                    value: activeCount,
                    detail: t('maintenance.active_mix', undefined, {
                        open: insights.open,
                        in_progress: insights.in_progress,
                    }),
                    icon: 'bi-tools',
                    tone: 'ink',
                },
                {
                    label: 'Urgent',
                    value: insights.urgent,
                    detail:
                        mode === 'manager'
                            ? t('maintenance.unassigned', undefined, {
                                  count: insights.unassigned,
                              })
                            : 'High-priority tenant issues',
                    icon: 'bi-exclamation-triangle',
                    tone: insights.urgent > 0 ? 'red' : 'teal',
                },
                {
                    label: 'Overdue',
                    value: insights.overdue,
                    detail: t('maintenance.resolved', undefined, {
                        count: insights.resolved,
                    }),
                    icon: 'bi-clock-history',
                    tone: insights.overdue > 0 ? 'amber' : 'blue',
                },
                {
                    label:
                        mode === 'manager'
                            ? 'Posted service cost'
                            : 'Request history',
                    value:
                        mode === 'manager'
                            ? currency(insights.posted_expenses, app.locale)
                            : insights.total,
                    detail: t('maintenance.total_requests', undefined, {
                        count: insights.total,
                    }),
                    icon:
                        mode === 'manager'
                            ? 'bi-cash-coin'
                            : 'bi-clock-history',
                    tone: 'teal',
                },
            ]}
        />
    );
}
