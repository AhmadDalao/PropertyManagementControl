import { useTranslator } from '@/lib/i18n';

import { DetailCard } from './detail-card';
import { DocumentStrip } from './document-strip';
import { HistoryTimeline } from './history-timeline';
import { RelatedRecordsTable } from './related-records-table';
import { ResourceHeader } from './resource-header';
import { ResourceProgressPanel } from './resource-progress-panel';
import { ResourceSpotlightPanel } from './resource-spotlight-panel';
import type { ResourceDetailShellProps } from './types';
import { WorkflowActionPanel } from './workflow-action-panel';

export function MaintenanceTriageLayout({
    header,
    spotlight,
    workflow,
    progress,
    stats = [],
    sections = [],
    related = [],
    documents = [],
    timeline = [],
}: ResourceDetailShellProps) {
    const { t, text } = useTranslator();

    return (
        <>
            <ResourceHeader {...header} />
            <section className="pmc-maintenance-triage-layout">
                <main>
                    {spotlight ? (
                        <ResourceSpotlightPanel spotlight={spotlight} />
                    ) : null}
                    {stats.length > 0 ? (
                        <section className="pmc-resource-stat-grid">
                            {stats.map((item) => (
                                <article
                                    key={item.label}
                                    className={`pmc-resource-stat pmc-resource-stat-${item.tone ?? 'muted'}`}
                                >
                                    <span>{text(item.label)}</span>
                                    <strong>{item.value ?? '-'}</strong>
                                </article>
                            ))}
                        </section>
                    ) : null}
                    {progress ? (
                        <ResourceProgressPanel progress={progress} />
                    ) : null}
                    <div className="pmc-resource-detail-stack">
                        {sections.map((section) => (
                            <DetailCard key={section.title} section={section} />
                        ))}
                    </div>
                    {related.length > 0 ? (
                        <details className="pmc-triage-related">
                            <summary>{t('resource.related_records')}</summary>
                            <div className="pmc-resource-detail-stack">
                                {related.map((table) => (
                                    <RelatedRecordsTable
                                        key={table.title}
                                        table={table}
                                    />
                                ))}
                            </div>
                        </details>
                    ) : null}
                </main>
                <aside>
                    {workflow ? (
                        <WorkflowActionPanel workflow={workflow} />
                    ) : null}
                    {documents.length > 0 ? (
                        <DocumentStrip documents={documents} />
                    ) : null}
                    {timeline.length > 0 ? (
                        <HistoryTimeline timeline={timeline} />
                    ) : null}
                </aside>
            </section>
        </>
    );
}
