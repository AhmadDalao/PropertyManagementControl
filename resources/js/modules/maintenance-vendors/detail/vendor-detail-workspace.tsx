import { useState } from 'react';

import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { ResourceHeader } from '@/components/resource-cycle/resource-header';

import type { VendorDetailPage, VendorDetailTab } from './types';
import { VendorDetailMetrics } from './vendor-detail-metrics';
import {
    requestedVendorTab,
    VendorDetailTabs,
    vendorTabs,
} from './vendor-detail-tabs';
import { VendorOverviewPanel } from './vendor-overview-panel';
import { VendorSectionPanel } from './vendor-section-panel';
import { VendorWorkloadPanel } from './vendor-workload-panel';

export function VendorDetailWorkspace({
    detail,
}: {
    detail: VendorDetailPage;
}) {
    const tabs = vendorTabs(detail);
    const available = tabs.map((tab) => tab.key);
    const [active, setActive] = useState<VendorDetailTab>(() =>
        requestedVendorTab(available),
    );

    const selectTab = (tab: VendorDetailTab) => {
        setActive(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...detail.header} />
            <VendorDetailMetrics stats={detail.stats} />
            <VendorDetailTabs
                tabs={tabs}
                active={active}
                onSelect={selectTab}
            />
            <section
                className="pmc-vendor-detail-panel"
                role="tabpanel"
                id="vendor-detail-panel"
                aria-labelledby={`vendor-tab-${active}`}
                tabIndex={0}
                data-testid="vendor-detail-panel"
            >
                {active === 'overview' ? (
                    <VendorOverviewPanel detail={detail} />
                ) : null}
                {active === 'workload' ? (
                    <VendorWorkloadPanel detail={detail} />
                ) : null}
                {active === 'schedule' || active === 'financial' ? (
                    <VendorSectionPanel detail={detail} sectionKey={active} />
                ) : null}
                {active === 'history' ? (
                    <HistoryTimeline timeline={detail.timeline} />
                ) : null}
            </section>
        </>
    );
}
