import { useState } from 'react';

import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { ResourceHeader } from '@/components/resource-cycle/resource-header';

import type { WorkOrderDetailPage, WorkOrderDetailTab } from './types';
import { WorkOrderDetailMetrics } from './work-order-detail-metrics';
import {
    requestedWorkOrderTab,
    WorkOrderDetailTabs,
    workOrderTabs,
} from './work-order-detail-tabs';
import { WorkOrderOverviewPanel } from './work-order-overview-panel';
import { WorkOrderSectionPanel } from './work-order-section-panel';

export function WorkOrderDetailWorkspace({
    detail,
}: {
    detail: WorkOrderDetailPage;
}) {
    const tabs = workOrderTabs(detail);
    const available = tabs.map((tab) => tab.key);
    const [active, setActive] = useState<WorkOrderDetailTab>(() =>
        requestedWorkOrderTab(available),
    );

    const selectTab = (tab: WorkOrderDetailTab) => {
        setActive(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...detail.header} />
            <WorkOrderDetailMetrics stats={detail.stats} />
            <WorkOrderDetailTabs
                tabs={tabs}
                active={active}
                onSelect={selectTab}
            />
            <section
                className="pmc-work-order-detail-panel"
                role="tabpanel"
                id="work-order-detail-panel"
                aria-labelledby={`work-order-tab-${active}`}
                tabIndex={0}
                data-testid="work-order-detail-panel"
            >
                {active === 'overview' ? (
                    <WorkOrderOverviewPanel detail={detail} />
                ) : null}
                {['assignment', 'schedule', 'cost', 'completion'].includes(
                    active,
                ) ? (
                    <WorkOrderSectionPanel
                        detail={detail}
                        sectionKey={
                            active as
                                | 'assignment'
                                | 'schedule'
                                | 'cost'
                                | 'completion'
                        }
                    />
                ) : null}
                {active === 'history' ? (
                    <HistoryTimeline timeline={detail.timeline} />
                ) : null}
            </section>
        </>
    );
}
