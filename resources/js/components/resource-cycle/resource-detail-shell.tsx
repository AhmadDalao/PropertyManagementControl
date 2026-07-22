import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import { DecisionCardGrid } from './decision-card-grid';
import { DetailCard } from './detail-card';
import { DocumentStrip } from './document-strip';
import { HistoryTimeline } from './history-timeline';
import { RelatedRecordsTable } from './related-records-table';
import { ResourceDetailTabs } from './resource-detail-tabs';
import type { ResourceDetailTabDefinition } from './resource-detail-tabs';
import { ResourceHeader } from './resource-header';
import { ResourceSpotlightPanel } from './resource-spotlight-panel';
import type {
    DetailSection,
    ResourceDetailShellProps,
    ResourceDetailTab,
} from './types';
import { WorkflowActionPanel } from './workflow-action-panel';

export function ResourceDetailShell({
    header,
    spotlight,
    workflow,
    decisionCards = [],
    stats = [],
    sections = [],
    related = [],
    documents = [],
    timeline = [],
}: ResourceDetailShellProps) {
    const financialSections = sections.filter(isFinancialSection);
    const overviewSections = sections.filter(
        (section) => !financialSections.includes(section),
    );
    const availableTabs = buildAvailableTabs({
        hasFinancial: financialSections.length > 0,
        hasDocuments: documents.length > 0,
        hasRelated: related.length > 0,
        hasHistory: timeline.length > 0,
    });
    const [activeTab, setActiveTab] = useState<ResourceDetailTab>(() =>
        requestedTab(availableTabs),
    );

    const selectTab = (tab: ResourceDetailTab) => {
        setActiveTab(tab);

        if (typeof window === 'undefined') {
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...header} />
            <ResourceDetailTabs
                tabs={availableTabs}
                activeTab={activeTab}
                onSelect={selectTab}
            />

            <section className="pmc-resource-tab-panel">
                {activeTab === 'overview' ? (
                    <OverviewPanel
                        spotlight={spotlight}
                        workflow={workflow}
                        decisionCards={decisionCards}
                        stats={stats}
                        sections={overviewSections}
                    />
                ) : null}

                {activeTab === 'financial' ? (
                    <DetailSectionStack sections={financialSections} />
                ) : null}

                {activeTab === 'documents' ? (
                    <DocumentStrip documents={documents} />
                ) : null}

                {activeTab === 'related' ? (
                    <div className="pmc-resource-detail-stack">
                        {related.map((table) => (
                            <RelatedRecordsTable
                                key={table.title}
                                table={table}
                            />
                        ))}
                    </div>
                ) : null}

                {activeTab === 'history' ? (
                    <HistoryTimeline timeline={timeline} />
                ) : null}
            </section>
        </>
    );
}

function OverviewPanel({
    spotlight,
    workflow,
    decisionCards,
    stats,
    sections,
}: Pick<
    ResourceDetailShellProps,
    'spotlight' | 'workflow' | 'decisionCards' | 'stats'
> & {
    sections: DetailSection[];
}) {
    const { text } = useTranslator();

    return (
        <>
            {spotlight ? (
                <ResourceSpotlightPanel spotlight={spotlight} />
            ) : null}
            {workflow ? <WorkflowActionPanel workflow={workflow} /> : null}
            {decisionCards && decisionCards.length > 0 ? (
                <DecisionCardGrid cards={decisionCards} />
            ) : null}
            {stats && stats.length > 0 ? (
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
            <DetailSectionStack sections={sections} />
        </>
    );
}

function DetailSectionStack({ sections }: { sections: DetailSection[] }) {
    return (
        <div className="pmc-resource-detail-stack">
            {sections.map((section) => (
                <DetailCard key={section.title} section={section} />
            ))}
        </div>
    );
}

function requestedTab(tabs: ResourceDetailTabDefinition[]): ResourceDetailTab {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URLSearchParams(window.location.search).get(
        'tab',
    ) as ResourceDetailTab | null;

    return tabs.some((tab) => tab.key === requested)
        ? (requested ?? 'overview')
        : 'overview';
}

function buildAvailableTabs({
    hasFinancial,
    hasDocuments,
    hasRelated,
    hasHistory,
}: {
    hasFinancial: boolean;
    hasDocuments: boolean;
    hasRelated: boolean;
    hasHistory: boolean;
}): ResourceDetailTabDefinition[] {
    return [
        { key: 'overview', label: 'Overview', icon: 'bi-grid' },
        ...(hasFinancial
            ? [
                  {
                      key: 'financial' as const,
                      label: 'Financial',
                      icon: 'bi-cash-stack',
                  },
              ]
            : []),
        ...(hasDocuments
            ? [
                  {
                      key: 'documents' as const,
                      label: 'Documents',
                      icon: 'bi-folder2-open',
                  },
              ]
            : []),
        ...(hasRelated
            ? [
                  {
                      key: 'related' as const,
                      label: 'Related',
                      icon: 'bi-diagram-3',
                  },
              ]
            : []),
        ...(hasHistory
            ? [
                  {
                      key: 'history' as const,
                      label: 'History',
                      icon: 'bi-clock-history',
                  },
              ]
            : []),
    ];
}

function isFinancialSection(section: DetailSection): boolean {
    return (
        section.tab === 'financial' ||
        (section.tab === undefined &&
            financialSectionPattern.test(section.title))
    );
}

const financialSectionPattern =
    /finance|financial|payment|rent|balance|contract|lease|expense|valuation|allocation|installment|deposit/i;
