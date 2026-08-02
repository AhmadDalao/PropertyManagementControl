import { useTranslator } from '@/lib/i18n';

import { DashboardSectionGroup } from '../shared/dashboard-section-group';
import type { OperationsDashboardProps } from '../types';
import { LaunchReadinessPanel } from './launch-readiness-panel';
import { OperationsInsightPanels } from './operations-insight-panels';
import { OperationsTodayWorkspace } from './operations-today-workspace';
import { PlatformActivityPanel } from './platform-activity-panel';
import { PlatformCompositionPanel } from './platform-composition-panel';
import { PlatformStatusPanel } from './platform-status-panel';
import { PropertyPerformanceGrid } from './property-performance-grid';

export function OperationsDashboardGroups({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { t } = useTranslator();

    return (
        <>
            <DashboardSectionGroup
                name="today"
                title={t('dashboard.mobile_today_title')}
                description={t('dashboard.mobile_today_description')}
                icon="bi-list-check"
                defaultOpen
            >
                <OperationsTodayWorkspace props={props} />
            </DashboardSectionGroup>

            <DashboardSectionGroup
                name="portfolio"
                title={t('dashboard.mobile_portfolio_title')}
                description={t('dashboard.mobile_portfolio_description')}
                icon="bi-buildings"
            >
                <PropertyPerformanceGrid props={props} />
                <OperationsInsightPanels props={props} />
            </DashboardSectionGroup>

            {props.mode === 'superadmin' ? (
                <DashboardSectionGroup
                    name="system"
                    title={t('dashboard.mobile_system_title')}
                    description={t('dashboard.mobile_system_description')}
                    icon="bi-sliders2"
                >
                    <PlatformActivityPanel
                        activities={props.platformActivity}
                    />
                    {props.platformComposition ? (
                        <PlatformCompositionPanel
                            composition={props.platformComposition}
                        />
                    ) : null}
                    {props.readinessStatus ? (
                        <LaunchReadinessPanel status={props.readinessStatus} />
                    ) : null}
                    {props.cmsStatus ? (
                        <PlatformStatusPanel status={props.cmsStatus} />
                    ) : null}
                </DashboardSectionGroup>
            ) : null}
        </>
    );
}
