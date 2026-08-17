import { useState } from 'react';

import { DocumentStrip } from '@/components/resource-cycle/document-strip';
import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { ResourceHeader } from '@/components/resource-cycle/resource-header';

import type { UserDetailPage, UserDetailTab } from './types';
import { UserDetailMetrics } from './user-detail-metrics';
import { requestedUserTab, UserDetailTabs, userTabs } from './user-detail-tabs';
import { UserOverviewPanel } from './user-overview-panel';
import { UserRecordPanel } from './user-record-panel';

export function UserDetailWorkspace({ detail }: { detail: UserDetailPage }) {
    const tabs = userTabs(detail);
    const available = tabs.map((tab) => tab.key);
    const [active, setActive] = useState<UserDetailTab>(() =>
        requestedUserTab(available),
    );

    const selectTab = (tab: UserDetailTab) => {
        setActive(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...detail.header} />
            <UserDetailMetrics stats={detail.stats} />
            <UserDetailTabs tabs={tabs} active={active} onSelect={selectTab} />
            <section
                className="pmc-user-detail-panel"
                role="tabpanel"
                id="user-detail-panel"
                aria-labelledby={`user-tab-${active}`}
                tabIndex={0}
                data-testid="user-detail-panel"
            >
                {active === 'overview' ? (
                    <UserOverviewPanel detail={detail} />
                ) : null}
                {active === 'access' ? (
                    <UserRecordPanel detail={detail} sectionKeys={['access']} />
                ) : null}
                {active === 'properties' ? (
                    <UserRecordPanel
                        detail={detail}
                        relatedKeys={['properties']}
                    />
                ) : null}
                {active === 'workload' ? (
                    <UserRecordPanel
                        detail={detail}
                        relatedKeys={['workload']}
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
