import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { OperationsDashboardProps } from '../types';
import { OperationsInsightPanels } from './operations-insight-panels';
import { OperationsSystemWorkspace } from './operations-system-workspace';
import { OperationsTodayWorkspace } from './operations-today-workspace';
import { OperationsViewTabs } from './operations-view-tabs';
import type { OperationsDashboardView } from './operations-view-tabs';
import { PropertyPerformanceGrid } from './property-performance-grid';

export function OperationsDashboardGroups({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t } = useTranslator();
    const views: OperationsDashboardView[] = [
        'today',
        'portfolio',
        ...(props.mode === 'superadmin' ? (['system'] as const) : []),
    ];
    const [active, setActive] = useState<OperationsDashboardView>(() =>
        initialView(views),
    );
    const selected = views.includes(active) ? active : 'today';
    const select = (view: OperationsDashboardView) => {
        setActive(view);
        const url = new URL(window.location.href);
        url.searchParams.set('panel', view);
        window.history.replaceState({}, '', url);
    };
    const attention =
        props.nextActions.length +
        props.collectionQueue.length +
        props.stats.openRequests +
        props.moveOutQueue.attention +
        props.moveOutQueue.ready;

    return (
        <section className="pmc-dashboard-workspace">
            <OperationsViewTabs
                active={selected}
                label={t('dashboard.workspace_navigation')}
                locale={locale}
                options={[
                    {
                        key: 'today',
                        label: t('dashboard.mobile_today_title'),
                        description: t('dashboard.mobile_today_description'),
                        icon: 'bi-list-check',
                        count: attention,
                    },
                    {
                        key: 'portfolio',
                        label: t('dashboard.mobile_portfolio_title'),
                        description: t(
                            'dashboard.mobile_portfolio_description',
                        ),
                        icon: 'bi-buildings',
                        count: props.propertyPerformance.length,
                    },
                    ...(props.mode === 'superadmin'
                        ? [
                              {
                                  key: 'system' as const,
                                  label: t('dashboard.mobile_system_title'),
                                  description: t(
                                      'dashboard.mobile_system_description',
                                  ),
                                  icon: 'bi-sliders2',
                                  count:
                                      (props.readinessStatus
                                          ?.automatic_blocked ?? 0) +
                                      (props.readinessStatus
                                          ?.evidence_remaining ?? 0),
                              },
                          ]
                        : []),
                ]}
                onSelect={select}
            />

            {selected === 'today' ? (
                <div
                    id="dashboard-panel-today"
                    className="pmc-dashboard-view-panel"
                    data-dashboard-group="today"
                    role="region"
                    aria-labelledby="dashboard-view-today"
                >
                    <OperationsTodayWorkspace props={props} />
                </div>
            ) : null}

            {selected === 'portfolio' ? (
                <div
                    id="dashboard-panel-portfolio"
                    className="pmc-dashboard-view-panel"
                    data-dashboard-group="portfolio"
                    role="region"
                    aria-labelledby="dashboard-view-portfolio"
                >
                    <PropertyPerformanceGrid props={props} />
                    <OperationsInsightPanels props={props} />
                </div>
            ) : null}

            {selected === 'system' && props.mode === 'superadmin' ? (
                <div
                    id="dashboard-panel-system"
                    className="pmc-dashboard-view-panel"
                    data-dashboard-group="system"
                    role="region"
                    aria-labelledby="dashboard-view-system"
                >
                    <OperationsSystemWorkspace props={props} />
                </div>
            ) : null}
        </section>
    );
}

function initialView(
    available: OperationsDashboardView[],
): OperationsDashboardView {
    if (typeof window === 'undefined') {
        return 'today';
    }

    const requested = new URLSearchParams(window.location.search).get('panel');

    return available.includes(requested as OperationsDashboardView)
        ? (requested as OperationsDashboardView)
        : 'today';
}
