import { useRef } from 'react';
import type { KeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { DocumentDetailPage, DocumentDetailTab } from './types';

type DocumentTab = { key: DocumentDetailTab; count: number | null };

export function documentTabs(detail: DocumentDetailPage): DocumentTab[] {
    return detail.availableTabs.map((key) => ({
        key,
        count: key === 'history' ? detail.timeline.length : null,
    }));
}

export function requestedDocumentTab(
    available: DocumentDetailTab[],
): DocumentDetailTab {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URL(window.location.href).searchParams.get('tab');

    return available.includes(requested as DocumentDetailTab)
        ? (requested as DocumentDetailTab)
        : 'overview';
}

export function DocumentDetailTabs({
    tabs,
    active,
    onSelect,
}: {
    tabs: DocumentTab[];
    active: DocumentDetailTab;
    onSelect: (tab: DocumentDetailTab) => void;
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
            className="pmc-document-detail-tabs"
            role="tablist"
            aria-label={t('documents.detail_navigation')}
            data-testid="document-detail-tabs"
        >
            {tabs.map((tab, index) => (
                <button
                    type="button"
                    role="tab"
                    id={`document-tab-${tab.key}`}
                    aria-controls="document-detail-panel"
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
                    <span>{t(`documents.tabs.${tab.key}`)}</span>
                    {tab.count !== null ? <strong>{tab.count}</strong> : null}
                </button>
            ))}
        </div>
    );
}
