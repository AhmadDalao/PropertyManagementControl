import { useState } from 'react';

import { ResourceHeader } from '@/components/resource-cycle';
import { DocumentStrip } from '@/components/resource-cycle/document-strip';
import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { RelatedRecordsTable } from '@/components/resource-cycle/related-records-table';
import { ResourceProgressPanel } from '@/components/resource-cycle/resource-progress-panel';
import { useTranslator } from '@/lib/i18n';

import { LeaseDetailCard } from './lease-detail-card';
import {
    LeaseDetailTabs,
    leaseTabs,
    relatedPanel,
    requestedLeaseTab,
} from './lease-detail-tabs';
import type { LeaseDetailTab } from './lease-detail-tabs';
import { LeaseNextStepPanel } from './lease-next-step-panel';
import type { LeaseDetailPage } from './types';

export function LeaseDetailWorkspace({ detail }: { detail: LeaseDetailPage }) {
    const { t, text } = useTranslator();
    const tabs = leaseTabs(detail);
    const available = tabs.map((tab) => tab.key);
    const [active, setActive] = useState<LeaseDetailTab>(() =>
        requestedLeaseTab(available),
    );
    const selectedRelated = relatedPanel(detail.related, active);
    const overview = detail.sections.find(
        (section) => section.tab === 'overview',
    );
    const financial = detail.sections.find(
        (section) => section.tab === 'financial',
    );

    const selectTab = (tab: LeaseDetailTab) => {
        setActive(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...detail.header} />
            <section
                className="pmc-lease-detail-metrics"
                aria-label={t('leases.lease_detail')}
            >
                {detail.stats.map((stat) => (
                    <article
                        className={`is-${stat.tone ?? 'muted'}`}
                        key={stat.label}
                    >
                        <span>{text(stat.label)}</span>
                        <strong>{stat.value ?? '-'}</strong>
                    </article>
                ))}
            </section>
            <LeaseDetailTabs tabs={tabs} active={active} onSelect={selectTab} />
            <section
                className="pmc-lease-detail-panel"
                role="tabpanel"
                id="lease-detail-panel"
                aria-labelledby={`lease-tab-${active}`}
                tabIndex={0}
            >
                {active === 'overview' && overview ? (
                    <div className="pmc-lease-overview-grid">
                        <main>
                            <LeaseDetailCard section={overview} />
                        </main>
                        <aside>
                            <LeaseNextStepPanel workflow={detail.workflow} />
                            {detail.progress ? (
                                <ResourceProgressPanel
                                    progress={detail.progress}
                                />
                            ) : null}
                        </aside>
                    </div>
                ) : null}
                {active === 'financial' && financial ? (
                    <LeaseDetailCard section={financial} />
                ) : null}
                {selectedRelated ? (
                    <RelatedRecordsTable table={selectedRelated} />
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
