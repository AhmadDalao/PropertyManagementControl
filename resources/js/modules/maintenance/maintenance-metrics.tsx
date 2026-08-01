import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import type { MaintenanceIndexPageProps } from './types';

type MaintenanceMetricsProps = Pick<
    MaintenanceIndexPageProps,
    'maintenanceInsights' | 'mode' | 'financialsEnabled' | 'app'
>;

export function MaintenanceMetrics({
    maintenanceInsights: insights,
    mode,
    financialsEnabled,
    app,
}: MaintenanceMetricsProps) {
    const { t } = useTranslator();
    const activeCount = insights.open + insights.in_progress;
    const showFinancials = mode === 'manager' && financialsEnabled;

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('maintenance.active_requests'),
                    value: activeCount,
                    detail: t('maintenance.active_mix', undefined, {
                        open: insights.open,
                        in_progress: insights.in_progress,
                    }),
                    icon: 'bi-tools',
                    tone: 'ink',
                },
                {
                    label: t('maintenance.urgent'),
                    value: insights.urgent,
                    detail:
                        mode === 'manager'
                            ? t('maintenance.unassigned', undefined, {
                                  count: insights.unassigned,
                              })
                            : t('maintenance.high_priority_issues'),
                    icon: 'bi-exclamation-triangle',
                    tone: insights.urgent > 0 ? 'red' : 'teal',
                },
                {
                    label: t('maintenance.overdue'),
                    value: insights.overdue,
                    detail: t('maintenance.pending_confirmations', undefined, {
                        count: insights.pending_confirmation,
                    }),
                    icon: 'bi-clock-history',
                    tone: insights.overdue > 0 ? 'amber' : 'blue',
                },
                {
                    label: showFinancials
                        ? t('maintenance.posted_service_cost')
                        : t('maintenance.request_history'),
                    value: showFinancials
                        ? currency(insights.posted_expenses, app.locale)
                        : insights.total,
                    detail: t('maintenance.total_requests', undefined, {
                        count: insights.total,
                    }),
                    icon: showFinancials ? 'bi-cash-coin' : 'bi-clock-history',
                    tone: 'teal',
                },
            ]}
        />
    );
}
