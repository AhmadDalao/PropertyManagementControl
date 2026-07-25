import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import { actionCenterUrl } from './action-center-query';
import type { ActionCenterFilters, ActionCenterMetricSet } from './types';

export function ActionCenterMetrics({
    filters,
    metrics,
}: {
    filters: ActionCenterFilters;
    metrics: ActionCenterMetricSet;
}) {
    const { locale, t } = useTranslator();
    const base = {
        priority: 'all' as const,
        assignee: 'all',
        page: 1,
    };

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('action_center.metric_total'),
                    value: localizedNumber(metrics.total, locale),
                    detail: t('action_center.metric_total_detail'),
                    icon: 'bi-collection',
                    tone: 'ink',
                    href: actionCenterUrl(filters, base),
                },
                {
                    label: t('action_center.metric_critical'),
                    value: localizedNumber(metrics.critical, locale),
                    detail: t('action_center.metric_critical_detail'),
                    icon: 'bi-exclamation-triangle',
                    tone: metrics.critical > 0 ? 'red' : 'teal',
                    href: actionCenterUrl(filters, {
                        ...base,
                        priority: 'critical',
                    }),
                },
                {
                    label: t('action_center.metric_high'),
                    value: localizedNumber(metrics.high, locale),
                    detail: t('action_center.metric_high_detail'),
                    icon: 'bi-clock-history',
                    tone: metrics.high > 0 ? 'amber' : 'teal',
                    href: actionCenterUrl(filters, {
                        ...base,
                        priority: 'high',
                    }),
                },
                {
                    label: t('action_center.metric_unassigned'),
                    value: localizedNumber(metrics.unassigned, locale),
                    detail: t('action_center.metric_unassigned_detail'),
                    icon: 'bi-person-exclamation',
                    tone: metrics.unassigned > 0 ? 'amber' : 'teal',
                    href: actionCenterUrl(filters, {
                        ...base,
                        assignee: 'unassigned',
                    }),
                },
            ]}
        />
    );
}
