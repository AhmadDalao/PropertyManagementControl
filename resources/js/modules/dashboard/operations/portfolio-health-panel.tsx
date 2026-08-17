import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { HealthSignals } from '../shared/health-signals';
import type { OperationsDashboardProps } from '../types';
import { propertyFocusUrl } from './property-focus-url';

export function PortfolioHealthPanel({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { t } = useTranslator();
    const propertyId = props.propertyFocus.selected?.id;
    const completedSetup = props.setupChecklist.filter(
        (item) => item.done,
    ).length;
    const occupiedAssets =
        Number(props.charts.occupancy.occupied ?? 0) +
        Number(props.charts.occupancy.partially_occupied ?? 0);
    const occupancyTotal = Object.values(props.charts.occupancy).reduce(
        (total, value) => total + Number(value),
        0,
    );
    const occupancyRate =
        occupancyTotal > 0
            ? Math.round((occupiedAssets / occupancyTotal) * 100)
            : 0;

    return (
        <WorkspacePanel
            eyebrow={t('dashboard.portfolio_health')}
            title={t('dashboard.portfolio_signals')}
            description={t('dashboard.portfolio_signals_description')}
        >
            <HealthSignals
                signals={[
                    {
                        label: t('dashboard.setup_completion'),
                        value:
                            props.setupChecklist.length > 0
                                ? Math.round(
                                      (completedSetup /
                                          props.setupChecklist.length) *
                                          100,
                                  )
                                : 100,
                        href: '/documentation',
                    },
                    {
                        label: t('dashboard.occupancy_rate'),
                        value: occupancyRate,
                        href: propertyFocusUrl(
                            '/property-explorer',
                            propertyId,
                        ),
                    },
                    {
                        label: t('dashboard.map_coverage'),
                        value: props.propertyMap.summary.coverage_percent,
                        href: propertyId
                            ? `/property-explorer?property_id=${propertyId}`
                            : '/property-map',
                    },
                ]}
            />
        </WorkspacePanel>
    );
}
