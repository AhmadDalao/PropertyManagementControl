import { useState } from 'react';

import { DocumentStrip } from '@/components/resource-cycle/document-strip';
import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { ResourceHeader } from '@/components/resource-cycle/resource-header';

import { AssetDetailMetrics } from './asset-detail-metrics';
import {
    AssetDetailTabs,
    assetTabs,
    requestedAssetTab,
} from './asset-detail-tabs';
import { AssetLocationPanel } from './asset-location-panel';
import { AssetOverviewPanel } from './asset-overview-panel';
import { AssetRecordPanel } from './asset-record-panel';
import type { AssetDetailPage, AssetDetailTab } from './types';

export function AssetDetailWorkspace({ detail }: { detail: AssetDetailPage }) {
    const tabs = assetTabs(detail);
    const available = tabs.map((tab) => tab.key);
    const [active, setActive] = useState<AssetDetailTab>(() =>
        requestedAssetTab(available),
    );

    const selectTab = (tab: AssetDetailTab) => {
        setActive(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...detail.header} />
            <AssetDetailMetrics stats={detail.stats} />
            <AssetDetailTabs tabs={tabs} active={active} onSelect={selectTab} />
            <section
                className="pmc-asset-detail-panel"
                role="tabpanel"
                id="asset-detail-panel"
                aria-labelledby={`asset-tab-${active}`}
                tabIndex={0}
                data-testid="asset-detail-panel"
            >
                {active === 'overview' ? (
                    <AssetOverviewPanel detail={detail} />
                ) : null}
                {active === 'structure' ? (
                    <div className="pmc-asset-record-stack">
                        <AssetLocationPanel spotlight={detail.spotlight} />
                        <AssetRecordPanel
                            detail={detail}
                            relatedKeys={['rentable_spaces', 'children']}
                        />
                    </div>
                ) : null}
                {active === 'leasing' ? (
                    <AssetRecordPanel
                        detail={detail}
                        sectionKeys={['active_rental']}
                        relatedKeys={['leases', 'collections']}
                    />
                ) : null}
                {active === 'financial' ? (
                    <AssetRecordPanel
                        detail={detail}
                        sectionKeys={['financial']}
                        relatedKeys={['expenses']}
                    />
                ) : null}
                {active === 'service' ? (
                    <AssetRecordPanel
                        detail={detail}
                        relatedKeys={['maintenance']}
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
