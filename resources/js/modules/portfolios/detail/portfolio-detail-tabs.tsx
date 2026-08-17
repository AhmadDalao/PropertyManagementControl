import { useRef } from 'react';
import type { KeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type {
    PortfolioDetailPage,
    PortfolioDetailTab,
    PortfolioRelatedKey,
} from './types';

type PortfolioTab = { key: PortfolioDetailTab; count: number | null };

export function portfolioTabs(detail: PortfolioDetailPage): PortfolioTab[] {
    return detail.availableTabs.map((key) => ({
        key,
        count: tabCount(detail, key),
    }));
}

export function requestedPortfolioTab(
    available: PortfolioDetailTab[],
): PortfolioDetailTab {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URL(window.location.href).searchParams.get('tab');
    const legacyTabs: Record<string, PortfolioDetailTab> = {
        related: 'properties',
        assets: 'properties',
        users: 'people',
        leases: 'operations',
        maintenance: 'operations',
    };
    const normalized = requested ? (legacyTabs[requested] ?? requested) : null;

    return available.includes(normalized as PortfolioDetailTab)
        ? (normalized as PortfolioDetailTab)
        : 'overview';
}

export function PortfolioDetailTabs({
    tabs,
    active,
    onSelect,
}: {
    tabs: PortfolioTab[];
    active: PortfolioDetailTab;
    onSelect: (tab: PortfolioDetailTab) => void;
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
            className="pmc-portfolio-detail-tabs"
            role="tablist"
            aria-orientation="horizontal"
            aria-label={t('portfolios.detail_navigation')}
            data-testid="portfolio-detail-tabs"
        >
            {tabs.map((tab, index) => (
                <button
                    type="button"
                    role="tab"
                    id={`portfolio-tab-${tab.key}`}
                    aria-controls="portfolio-detail-panel"
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
                    <span>{t(`portfolios.tabs.${tab.key}`)}</span>
                    {tab.count !== null ? <strong>{tab.count}</strong> : null}
                </button>
            ))}
        </div>
    );
}

function tabCount(
    detail: PortfolioDetailPage,
    tab: PortfolioDetailTab,
): number | null {
    const keys: Partial<Record<PortfolioDetailTab, PortfolioRelatedKey[]>> = {
        properties: ['properties'],
        people: ['people'],
        operations: ['leases', 'maintenance'],
    };

    if (tab === 'documents') {
        return detail.documents.length;
    }

    if (tab === 'history') {
        return detail.timeline.length;
    }

    if (!keys[tab]) {
        return null;
    }

    return detail.related
        .filter((table) => keys[tab]?.includes(table.key))
        .reduce((total, table) => total + table.rows.length, 0);
}
