import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { OperationsDashboardProps } from '../types';
import { platformMetrics } from './platform-metrics';
import { portfolioMetrics } from './portfolio-metrics';

export function OperationsMetrics({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t } = useTranslator();

    return (
        <MetricGrid
            className="pmc-dashboard-metrics"
            metrics={
                props.mode === 'portfolio'
                    ? portfolioMetrics(props, locale, t)
                    : platformMetrics(props, locale, t)
            }
        />
    );
}
