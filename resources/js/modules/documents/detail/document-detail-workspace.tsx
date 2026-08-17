import { useState } from 'react';

import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { ResourceHeader } from '@/components/resource-cycle/resource-header';

import { DocumentAccessPanel } from './document-access-panel';
import { DocumentDetailMetrics } from './document-detail-metrics';
import {
    DocumentDetailTabs,
    documentTabs,
    requestedDocumentTab,
} from './document-detail-tabs';
import { DocumentOverviewPanel } from './document-overview-panel';
import { DocumentValidityPanel } from './document-validity-panel';
import type { DocumentDetailPage, DocumentDetailTab } from './types';

export function DocumentDetailWorkspace({
    detail,
}: {
    detail: DocumentDetailPage;
}) {
    const tabs = documentTabs(detail);
    const available = tabs.map((tab) => tab.key);
    const [active, setActive] = useState<DocumentDetailTab>(() =>
        requestedDocumentTab(available),
    );

    const selectTab = (tab: DocumentDetailTab) => {
        setActive(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...detail.header} />
            <DocumentDetailMetrics stats={detail.stats} />
            <DocumentDetailTabs
                tabs={tabs}
                active={active}
                onSelect={selectTab}
            />
            <section
                className="pmc-document-detail-panel"
                role="tabpanel"
                id="document-detail-panel"
                aria-labelledby={`document-tab-${active}`}
                tabIndex={0}
                data-testid="document-detail-panel"
            >
                {active === 'overview' ? (
                    <DocumentOverviewPanel detail={detail} />
                ) : null}
                {active === 'access' ? (
                    <DocumentAccessPanel detail={detail} />
                ) : null}
                {active === 'validity' ? (
                    <DocumentValidityPanel detail={detail} />
                ) : null}
                {active === 'history' ? (
                    <HistoryTimeline timeline={detail.timeline} />
                ) : null}
            </section>
        </>
    );
}
