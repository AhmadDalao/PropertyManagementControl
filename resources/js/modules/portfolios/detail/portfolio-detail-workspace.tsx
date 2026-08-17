import { useState } from 'react';

import { DocumentStrip } from '@/components/resource-cycle/document-strip';
import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { ResourceHeader } from '@/components/resource-cycle/resource-header';

import { PortfolioDetailMetrics } from './portfolio-detail-metrics';
import {
    PortfolioDetailTabs,
    portfolioTabs,
    requestedPortfolioTab,
} from './portfolio-detail-tabs';
import { PortfolioOverviewPanel } from './portfolio-overview-panel';
import { PortfolioPeoplePanel } from './portfolio-people-panel';
import { PortfolioRecordPanel } from './portfolio-record-panel';
import type { PortfolioDetailPage, PortfolioDetailTab } from './types';

export function PortfolioDetailWorkspace({
    detail,
}: {
    detail: PortfolioDetailPage;
}) {
    const tabs = portfolioTabs(detail);
    const available = tabs.map((tab) => tab.key);
    const [active, setActive] = useState<PortfolioDetailTab>(() =>
        requestedPortfolioTab(available),
    );

    const selectTab = (tab: PortfolioDetailTab) => {
        setActive(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...detail.header} />
            <PortfolioDetailMetrics stats={detail.stats} />
            <PortfolioDetailTabs
                tabs={tabs}
                active={active}
                onSelect={selectTab}
            />
            <section
                className="pmc-portfolio-detail-panel"
                role="tabpanel"
                id="portfolio-detail-panel"
                aria-labelledby={`portfolio-tab-${active}`}
                tabIndex={0}
                data-testid="portfolio-detail-panel"
            >
                {active === 'overview' ? (
                    <PortfolioOverviewPanel detail={detail} />
                ) : null}
                {active === 'properties' ? (
                    <PortfolioRecordPanel
                        detail={detail}
                        relatedKeys={['properties']}
                    />
                ) : null}
                {active === 'people' ? (
                    <PortfolioPeoplePanel detail={detail} />
                ) : null}
                {active === 'operations' ? (
                    <PortfolioRecordPanel
                        detail={detail}
                        relatedKeys={['leases', 'maintenance']}
                    />
                ) : null}
                {active === 'financial' ? (
                    <PortfolioRecordPanel
                        detail={detail}
                        sectionKeys={['financial']}
                    />
                ) : null}
                {active === 'documents' ? (
                    <DocumentStrip documents={detail.documents} />
                ) : null}
                {active === 'history' ? (
                    <HistoryTimeline timeline={detail.timeline} />
                ) : null}
            </section>
        </>
    );
}
