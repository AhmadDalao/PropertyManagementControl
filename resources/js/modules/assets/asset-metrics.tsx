import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { compactCurrency } from '@/lib/utils';

import type { AssetInsights } from './types';

type AssetMetricsProps = {
    insights: AssetInsights;
    locale: string;
};

export function AssetMetrics({ insights, locale }: AssetMetricsProps) {
    const { t } = useTranslator();
    const assignmentGaps = insights.missing_owner + insights.missing_manager;

    return (
        <MetricGrid
            metrics={[
                {
                    label: 'Assets',
                    value: insights.total_assets,
                    detail: t('assets.mix', undefined, {
                        buildings: insights.buildings,
                        units: insights.units,
                    }),
                    icon: 'bi-buildings',
                    tone: 'ink',
                },
                {
                    label: 'Portfolio value',
                    value: compactCurrency(insights.total_value, locale),
                    detail: t('assets.recorded_valuation'),
                    icon: 'bi-bank',
                    tone: 'blue',
                },
                {
                    label: 'Occupancy',
                    value: `${insights.rentable_occupancy_rate}%`,
                    detail: t('assets.vacant_rentable', undefined, {
                        count: insights.vacant_rentable_assets,
                    }),
                    icon: 'bi-house-check',
                    tone: 'teal',
                },
                {
                    label: 'Assignment gaps',
                    value: assignmentGaps,
                    detail: t('assets.assignment_gaps', undefined, {
                        owners: insights.missing_owner,
                        managers: insights.missing_manager,
                    }),
                    icon: 'bi-person-exclamation',
                    tone: assignmentGaps > 0 ? 'red' : 'amber',
                },
            ]}
        />
    );
}
