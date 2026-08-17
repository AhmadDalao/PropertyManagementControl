import { useRef } from 'react';
import type { KeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { WorkOrderDetailPage, WorkOrderDetailTab } from './types';

type WorkOrderTab = { key: WorkOrderDetailTab; count: number | null };

export function workOrderTabs(detail: WorkOrderDetailPage): WorkOrderTab[] {
    return detail.availableTabs.map((key) => ({
        key,
        count: key === 'history' ? detail.timeline.length : null,
    }));
}

export function requestedWorkOrderTab(
    available: WorkOrderDetailTab[],
): WorkOrderDetailTab {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URL(window.location.href).searchParams.get('tab');

    return available.includes(requested as WorkOrderDetailTab)
        ? (requested as WorkOrderDetailTab)
        : 'overview';
}

export function WorkOrderDetailTabs({
    tabs,
    active,
    onSelect,
}: {
    tabs: WorkOrderTab[];
    active: WorkOrderDetailTab;
    onSelect: (tab: WorkOrderDetailTab) => void;
}) {
    const { t } = useTranslator();
    const buttonRefs = useRef<Array<HTMLButtonElement | null>>([]);

    const moveFocus = (
        event: KeyboardEvent<HTMLButtonElement>,
        index: number,
    ) => {
        const rtl = document.documentElement.dir === 'rtl';
        const offset =
            event.key === 'ArrowRight' ? (rtl ? -1 : 1) : rtl ? 1 : -1;
        const requested =
            event.key === 'Home'
                ? 0
                : event.key === 'End'
                  ? tabs.length - 1
                  : ['ArrowRight', 'ArrowLeft'].includes(event.key)
                    ? index + offset
                    : null;

        if (requested === null) {
            return;
        }

        event.preventDefault();
        const next = (requested + tabs.length) % tabs.length;
        onSelect(tabs[next].key);
        buttonRefs.current[next]?.focus();
    };

    return (
        <div
            className="pmc-work-order-detail-tabs"
            role="tablist"
            aria-label={t('work_orders.detail_navigation')}
            data-testid="work-order-detail-tabs"
        >
            {tabs.map((tab, index) => (
                <button
                    type="button"
                    role="tab"
                    id={`work-order-tab-${tab.key}`}
                    aria-controls="work-order-detail-panel"
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
                    <span>{t(`work_orders.tabs.${tab.key}`)}</span>
                    {tab.count !== null ? <strong>{tab.count}</strong> : null}
                </button>
            ))}
        </div>
    );
}
