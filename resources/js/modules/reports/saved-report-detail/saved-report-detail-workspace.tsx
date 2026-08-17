import { useState } from 'react';

import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { ResourceHeader } from '@/components/resource-cycle/resource-header';

import { SavedReportDetailMetrics } from './saved-report-detail-metrics';
import {
    requestedSavedReportTab,
    SavedReportDetailTabs,
    savedReportTabs,
} from './saved-report-detail-tabs';
import { SavedReportOutputPanel } from './saved-report-output-panel';
import { SavedReportOverviewPanel } from './saved-report-overview-panel';
import { SavedReportSectionPanel } from './saved-report-section-panel';
import type { SavedReportDetailPage, SavedReportDetailTab } from './types';

export function SavedReportDetailWorkspace({
    detail,
}: {
    detail: SavedReportDetailPage;
}) {
    const tabs = savedReportTabs(detail);
    const available = tabs.map((tab) => tab.key);
    const [active, setActive] = useState<SavedReportDetailTab>(() =>
        requestedSavedReportTab(available),
    );

    const selectTab = (tab: SavedReportDetailTab) => {
        setActive(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...detail.header} />
            <SavedReportDetailMetrics stats={detail.stats} />
            <SavedReportDetailTabs
                tabs={tabs}
                active={active}
                onSelect={selectTab}
            />
            <section
                className="pmc-saved-report-detail-panel"
                role="tabpanel"
                id="saved-report-detail-panel"
                aria-labelledby={`saved-report-tab-${active}`}
                tabIndex={0}
                data-testid="saved-report-detail-panel"
            >
                {active === 'overview' ? (
                    <SavedReportOverviewPanel detail={detail} />
                ) : null}
                {active === 'scope' || active === 'access' ? (
                    <SavedReportSectionPanel
                        detail={detail}
                        sectionKey={active}
                    />
                ) : null}
                {active === 'outputs' ? (
                    <SavedReportOutputPanel detail={detail} />
                ) : null}
                {active === 'history' ? (
                    <HistoryTimeline timeline={detail.timeline} />
                ) : null}
            </section>
        </>
    );
}
