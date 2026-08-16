import { Link } from '@inertiajs/react';
import { useState } from 'react';

import { ResourceHeader } from '@/components/resource-cycle';
import { DocumentStrip } from '@/components/resource-cycle/document-strip';
import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { RelatedRecordsTable } from '@/components/resource-cycle/related-records-table';
import { useTranslator } from '@/lib/i18n';

import { MaintenanceContextCard } from './maintenance-context-card';
import {
    MaintenanceDetailTabs,
    maintenanceTabs,
    relatedPanel,
    requestedMaintenanceTab,
} from './maintenance-detail-tabs';
import type { MaintenanceDetailTab } from './maintenance-detail-tabs';
import { MaintenanceLifecyclePanel } from './maintenance-lifecycle-panel';
import { MaintenanceNextStepPanel } from './maintenance-next-step-panel';
import type { MaintenanceDetailPage } from './types';

export function MaintenanceDetailWorkspace({
    detail,
}: {
    detail: MaintenanceDetailPage;
}) {
    const { t, text } = useTranslator();
    const tabs = maintenanceTabs(detail);
    const available = tabs.map((tab) => tab.key);
    const [active, setActive] = useState<MaintenanceDetailTab>(() =>
        requestedMaintenanceTab(available),
    );
    const selectedRelated = relatedPanel(detail.related, active);
    const addEvidence = detail.header.actions?.find((action) =>
        action.href.includes('/attachments/create'),
    );

    const selectTab = (tab: MaintenanceDetailTab) => {
        setActive(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...detail.header} />
            <section
                className="pmc-maintenance-detail-metrics"
                aria-label={t('maintenance.request_context')}
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
            <MaintenanceDetailTabs
                tabs={tabs}
                active={active}
                onSelect={selectTab}
            />
            <section
                className="pmc-maintenance-detail-panel"
                role="tabpanel"
                id="maintenance-detail-panel"
                aria-labelledby={`maintenance-tab-${active}`}
                tabIndex={0}
            >
                {active === 'overview' ? (
                    <div className="pmc-maintenance-overview-grid">
                        <main>
                            <MaintenanceContextCard
                                items={detail.requestContext}
                                description={detail.sections[0]?.description}
                            />
                            <MaintenanceContextCard
                                title={t('maintenance.service_context')}
                                description={t(
                                    'maintenance.service_context_help',
                                )}
                                items={detail.serviceContext}
                            />
                        </main>
                        <aside>
                            <MaintenanceLifecyclePanel
                                progress={detail.progress}
                            />
                            <MaintenanceNextStepPanel
                                workflow={detail.workflow}
                            />
                        </aside>
                    </div>
                ) : null}
                {selectedRelated ? (
                    <RelatedRecordsTable table={selectedRelated} />
                ) : null}
                {active === 'evidence' ? (
                    detail.documents.length > 0 ? (
                        <DocumentStrip documents={detail.documents} />
                    ) : (
                        <div className="pmc-maintenance-empty-panel">
                            <i className="bi bi-images" aria-hidden="true" />
                            <p>{t('maintenance.no_evidence')}</p>
                            {addEvidence ? (
                                <Link
                                    href={addEvidence.href}
                                    className="btn btn-primary"
                                >
                                    {text(addEvidence.label)}
                                </Link>
                            ) : null}
                        </div>
                    )
                ) : null}
                {active === 'history' ? (
                    <HistoryTimeline timeline={detail.timeline} />
                ) : null}
            </section>
        </>
    );
}
