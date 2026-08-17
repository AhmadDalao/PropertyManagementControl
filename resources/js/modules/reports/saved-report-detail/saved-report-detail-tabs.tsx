import { useRef } from 'react';
import type { KeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { SavedReportDetailPage, SavedReportDetailTab } from './types';

type SavedReportTab = { key: SavedReportDetailTab; count: number | null };

export function savedReportTabs(
    detail: SavedReportDetailPage,
): SavedReportTab[] {
    return detail.availableTabs.map((key) => ({
        key,
        count:
            key === 'outputs'
                ? detail.outputs.length
                : key === 'history'
                  ? detail.timeline.length
                  : null,
    }));
}

export function requestedSavedReportTab(
    available: SavedReportDetailTab[],
): SavedReportDetailTab {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URL(window.location.href).searchParams.get('tab');

    return available.includes(requested as SavedReportDetailTab)
        ? (requested as SavedReportDetailTab)
        : 'overview';
}

export function SavedReportDetailTabs({
    tabs,
    active,
    onSelect,
}: {
    tabs: SavedReportTab[];
    active: SavedReportDetailTab;
    onSelect: (tab: SavedReportDetailTab) => void;
}) {
    const { t } = useTranslator();
    const refs = useRef<Array<HTMLButtonElement | null>>([]);

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
        refs.current[next]?.focus();
    };

    return (
        <div
            className="pmc-saved-report-detail-tabs"
            role="tablist"
            aria-label={t('reports.saved_report_navigation')}
            data-testid="saved-report-detail-tabs"
        >
            {tabs.map((tab, index) => (
                <button
                    type="button"
                    role="tab"
                    id={`saved-report-tab-${tab.key}`}
                    aria-controls="saved-report-detail-panel"
                    aria-selected={tab.key === active}
                    tabIndex={tab.key === active ? 0 : -1}
                    className={tab.key === active ? 'is-active' : ''}
                    onClick={() => onSelect(tab.key)}
                    onKeyDown={(event) => moveFocus(event, index)}
                    ref={(button) => {
                        refs.current[index] = button;
                    }}
                    key={tab.key}
                >
                    <span>{t(`reports.saved_report_tabs.${tab.key}`)}</span>
                    {tab.count !== null ? <strong>{tab.count}</strong> : null}
                </button>
            ))}
        </div>
    );
}
