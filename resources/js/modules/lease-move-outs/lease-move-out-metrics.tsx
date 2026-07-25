import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { LeaseMoveOutPageProps } from './types';

type MoveOutMetricsProps = Pick<
    LeaseMoveOutPageProps,
    'moveOutInsights' | 'app'
>;

export function LeaseMoveOutMetrics({
    moveOutInsights,
    app,
}: MoveOutMetricsProps) {
    const { t } = useTranslator();
    const count = (value: number) => localizedNumber(value, app.locale);

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('lease_move_outs.planned'),
                    value: count(moveOutInsights.planned),
                    detail: t('lease_move_outs.planned_help'),
                    icon: 'bi-calendar-event',
                    tone: 'blue',
                    href: '/lease-move-outs?queue=all',
                },
                {
                    label: t('lease_move_outs.attention'),
                    value: count(moveOutInsights.attention),
                    detail: t('lease_move_outs.attention_help'),
                    icon: 'bi-exclamation-circle',
                    tone: moveOutInsights.attention > 0 ? 'red' : 'teal',
                    href: '/lease-move-outs?queue=attention',
                },
                {
                    label: t('lease_move_outs.ready'),
                    value: count(moveOutInsights.ready),
                    detail: t('lease_move_outs.ready_help'),
                    icon: 'bi-box-arrow-right',
                    tone: 'teal',
                    href: '/lease-move-outs?queue=ready',
                },
                {
                    label: t('lease_move_outs.completed_30_days'),
                    value: count(moveOutInsights.completed_30_days),
                    detail: t('lease_move_outs.completed_30_days_help'),
                    icon: 'bi-check2-circle',
                    tone: 'ink',
                    href: '/lease-move-outs?queue=completed',
                },
            ]}
        />
    );
}
