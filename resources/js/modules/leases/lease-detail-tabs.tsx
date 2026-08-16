import { useRef } from 'react';
import type { KeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { LeaseDetailPage, LeaseRelatedTable } from './types';

export type LeaseDetailTab =
    | 'overview'
    | 'financial'
    | 'installments'
    | 'payments'
    | 'documents'
    | 'history';

export function leaseTabs(detail: LeaseDetailPage) {
    const related = new Map(
        detail.related.map((table) => [table.key, table] as const),
    );

    return [
        { key: 'overview' as const, count: null },
        { key: 'financial' as const, count: null },
        ...(['installments', 'payments'] as const)
            .filter((key) => related.has(key))
            .map((key) => ({ key, count: related.get(key)?.rows.length ?? 0 })),
        { key: 'documents' as const, count: detail.documents.length },
        ...(detail.timeline.length > 0
            ? [{ key: 'history' as const, count: detail.timeline.length }]
            : []),
    ];
}

export function requestedLeaseTab(available: LeaseDetailTab[]): LeaseDetailTab {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URL(window.location.href).searchParams.get('tab');
    const normalized = requested === 'related' ? 'installments' : requested;

    return available.includes(normalized as LeaseDetailTab)
        ? (normalized as LeaseDetailTab)
        : 'overview';
}

export function LeaseDetailTabs({
    tabs,
    active,
    onSelect,
}: {
    tabs: ReturnType<typeof leaseTabs>;
    active: LeaseDetailTab;
    onSelect: (tab: LeaseDetailTab) => void;
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
            className="pmc-lease-detail-tabs"
            role="tablist"
            aria-orientation="horizontal"
            aria-label={t('leases.detail_navigation')}
        >
            {tabs.map((tab, index) => (
                <button
                    type="button"
                    role="tab"
                    id={`lease-tab-${tab.key}`}
                    aria-controls="lease-detail-panel"
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
    tables: LeaseRelatedTable[],
    active: LeaseDetailTab,
) {
    return tables.find((table) => table.key === active);
}

function tabLabel(
    tab: LeaseDetailTab,
    t: ReturnType<typeof useTranslator>['t'],
) {
    const labels = {
        overview: t('leases.overview_tab'),
        financial: t('leases.financial_tab'),
        installments: t('leases.installments'),
        payments: t('leases.payments'),
        documents: t('leases.documents_tab'),
        history: t('leases.history_tab'),
    };

    return labels[tab];
}
