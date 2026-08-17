import { useRef } from 'react';
import type { KeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { AssetDetailPage, AssetDetailTab, AssetRelatedKey } from './types';

type AssetTab = { key: AssetDetailTab; count: number | null };

export function assetTabs(detail: AssetDetailPage): AssetTab[] {
    return detail.availableTabs.map((key) => ({
        key,
        count: tabCount(detail, key),
    }));
}

export function requestedAssetTab(available: AssetDetailTab[]): AssetDetailTab {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URL(window.location.href).searchParams.get('tab');
    const legacyTabs: Record<string, AssetDetailTab> = {
        related: 'structure',
        map: 'structure',
        leases: 'leasing',
        maintenance: 'service',
        expenses: 'financial',
    };
    const normalized = requested ? (legacyTabs[requested] ?? requested) : null;

    return available.includes(normalized as AssetDetailTab)
        ? (normalized as AssetDetailTab)
        : 'overview';
}

export function AssetDetailTabs({
    tabs,
    active,
    onSelect,
}: {
    tabs: AssetTab[];
    active: AssetDetailTab;
    onSelect: (tab: AssetDetailTab) => void;
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
            className="pmc-asset-detail-tabs"
            role="tablist"
            aria-orientation="horizontal"
            aria-label={t('assets.detail_navigation')}
            data-testid="asset-detail-tabs"
        >
            {tabs.map((tab, index) => (
                <button
                    type="button"
                    role="tab"
                    id={`asset-tab-${tab.key}`}
                    aria-controls="asset-detail-panel"
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
                    <span>{t(`assets.tabs.${tab.key}`)}</span>
                    {tab.count !== null ? <strong>{tab.count}</strong> : null}
                </button>
            ))}
        </div>
    );
}

function tabCount(detail: AssetDetailPage, tab: AssetDetailTab): number | null {
    const keys: Partial<Record<AssetDetailTab, AssetRelatedKey[]>> = {
        structure: ['rentable_spaces', 'children'],
        leasing: ['leases', 'collections'],
        financial: ['expenses'],
        service: ['maintenance'],
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
