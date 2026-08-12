import type { ResourceDetailTabDefinition } from './resource-detail-tabs';
import type { ResourceDetailTab } from './types';

export function requestedTab(
    tabs: ResourceDetailTabDefinition[],
): ResourceDetailTab {
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

export function buildAvailableTabs({
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
