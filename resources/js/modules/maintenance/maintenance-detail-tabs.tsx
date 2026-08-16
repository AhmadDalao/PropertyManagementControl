import { useRef } from 'react';
import type { KeyboardEvent } from 'react';

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
    const buttonRefs = useRef<Array<HTMLButtonElement | null>>([]);

    const moveFocus = (
        event: KeyboardEvent<HTMLButtonElement>,
        index: number,
    ) => {
        const isRtl = document.documentElement.dir === 'rtl';
        let nextIndex: number | null = null;

        if (event.key === 'Home') {
            nextIndex = 0;
        } else if (event.key === 'End') {
            nextIndex = tabs.length - 1;
        } else if (event.key === 'ArrowRight') {
            nextIndex = index + (isRtl ? -1 : 1);
        } else if (event.key === 'ArrowLeft') {
            nextIndex = index + (isRtl ? 1 : -1);
        }

        if (nextIndex === null) {
            return;
        }

        event.preventDefault();
        const wrappedIndex = (nextIndex + tabs.length) % tabs.length;
        onSelect(tabs[wrappedIndex].key);
        buttonRefs.current[wrappedIndex]?.focus();
    };

    return (
        <div
            className="pmc-maintenance-detail-tabs"
            role="tablist"
            aria-orientation="horizontal"
            aria-label={t('maintenance.detail_navigation')}
        >
            {tabs.map((tab, index) => (
                <button
                    type="button"
                    role="tab"
                    id={`maintenance-tab-${tab.key}`}
                    aria-controls="maintenance-detail-panel"
                    aria-selected={tab.key === active}
                    tabIndex={tab.key === active ? 0 : -1}
                    className={tab.key === active ? 'is-active' : ''}
                    onClick={() => onSelect(tab.key)}
                    onKeyDown={(event) => moveFocus(event, index)}
                    ref={(button) => {
                        buttonRefs.current[index] = button;
                    }}
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
