import { useTranslator } from '@/lib/i18n';

import type { MaintenanceDetailPage, MaintenanceRelatedTable } from './types';

export type MaintenanceDetailTab =
    | 'overview'
    | 'work-orders'
    | 'updates'
    | 'expenses'
    | 'evidence'
    | 'history';

export function maintenanceTabs(detail: MaintenanceDetailPage) {
    const related = new Map(
        detail.related.map((table) => [table.key, table] as const),
    );

    return [
        { key: 'overview' as const, count: null },
        ...(['work-orders', 'updates', 'expenses'] as const)
            .filter((key) => related.has(key))
            .map((key) => ({ key, count: related.get(key)?.rows.length ?? 0 })),
        { key: 'evidence' as const, count: detail.documents.length },
        ...(detail.timeline.length > 0
            ? [{ key: 'history' as const, count: detail.timeline.length }]
            : []),
    ];
}

export function requestedMaintenanceTab(
    available: MaintenanceDetailTab[],
): MaintenanceDetailTab {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URL(window.location.href).searchParams.get('tab');
    const normalized =
        requested === 'documents'
            ? 'evidence'
            : requested === 'related'
              ? 'work-orders'
              : requested;

    return available.includes(normalized as MaintenanceDetailTab)
        ? (normalized as MaintenanceDetailTab)
        : 'overview';
}

export function MaintenanceDetailTabs({
    tabs,
    active,
    onSelect,
}: {
    tabs: ReturnType<typeof maintenanceTabs>;
    active: MaintenanceDetailTab;
    onSelect: (tab: MaintenanceDetailTab) => void;
}) {
    const { t } = useTranslator();

    return (
        <div
            className="pmc-maintenance-detail-tabs"
            role="tablist"
            aria-label={t('maintenance.detail_navigation')}
        >
            {tabs.map((tab) => (
                <button
                    type="button"
                    role="tab"
                    aria-selected={tab.key === active}
                    className={tab.key === active ? 'is-active' : ''}
                    onClick={() => onSelect(tab.key)}
                    key={tab.key}
                >
                    <span>{tabLabel(tab.key, t)}</span>
                    {tab.count !== null ? <strong>{tab.count}</strong> : null}
                </button>
            ))}
        </div>
    );
}

export function relatedPanel(
    tables: MaintenanceRelatedTable[],
    active: MaintenanceDetailTab,
) {
    return tables.find((table) => table.key === active);
}

function tabLabel(
    tab: MaintenanceDetailTab,
    t: ReturnType<typeof useTranslator>['t'],
) {
    const labels = {
        overview: t('maintenance.overview_tab'),
        'work-orders': t('maintenance.work_orders'),
        updates: t('maintenance.updates'),
        expenses: t('maintenance.expenses'),
        evidence: t('maintenance.evidence_tab'),
        history: t('maintenance.history_tab'),
    };

    return labels[tab];
}
