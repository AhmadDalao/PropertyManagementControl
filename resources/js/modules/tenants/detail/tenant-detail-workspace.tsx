import { useState } from 'react';

import { DocumentStrip } from '@/components/resource-cycle/document-strip';
import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { ResourceHeader } from '@/components/resource-cycle/resource-header';

import { TenantDetailMetrics } from './tenant-detail-metrics';
import {
    TenantDetailTabs,
    requestedTenantTab,
    tenantTabs,
} from './tenant-detail-tabs';
import { TenantOverviewPanel } from './tenant-overview-panel';
import { TenantRecordPanel } from './tenant-record-panel';
import type { TenantDetailPage, TenantDetailTab } from './types';

export function TenantDetailWorkspace({
    detail,
}: {
    detail: TenantDetailPage;
}) {
    const tabs = tenantTabs(detail);
    const available = tabs.map((tab) => tab.key);
    const [active, setActive] = useState<TenantDetailTab>(() =>
        requestedTenantTab(available),
    );

    const selectTab = (tab: TenantDetailTab) => {
        setActive(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...detail.header} />
            <TenantDetailMetrics stats={detail.stats} />
            <TenantDetailTabs
                tabs={tabs}
                active={active}
                onSelect={selectTab}
            />
            <section
                className="pmc-tenant-detail-panel"
                role="tabpanel"
                id="tenant-detail-panel"
                aria-labelledby={`tenant-tab-${active}`}
                tabIndex={0}
                data-testid="tenant-detail-panel"
            >
                {active === 'overview' ? (
                    <TenantOverviewPanel detail={detail} />
                ) : null}
                {active === 'rental' ? (
                    <TenantRecordPanel
                        detail={detail}
                        sectionKeys={['rental']}
                        relatedKeys={['leases']}
                    />
                ) : null}
                {active === 'payments' ? (
                    <TenantRecordPanel
                        detail={detail}
                        sectionKeys={['financial']}
                        relatedKeys={['payments']}
                    />
                ) : null}
                {active === 'service' ? (
                    <TenantRecordPanel
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
