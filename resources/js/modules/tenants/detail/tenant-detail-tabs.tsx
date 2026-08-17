import { useRef } from 'react';
import type { KeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type {
    TenantDetailPage,
    TenantDetailTab,
    TenantRelatedKey,
} from './types';

type TenantTab = { key: TenantDetailTab; count: number | null };

export function tenantTabs(detail: TenantDetailPage): TenantTab[] {
    return detail.availableTabs.map((key) => ({
        key,
        count: tabCount(detail, key),
    }));
}

export function requestedTenantTab(
    available: TenantDetailTab[],
): TenantDetailTab {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URL(window.location.href).searchParams.get('tab');
    const legacyTabs: Record<string, TenantDetailTab> = {
        financial: 'payments',
        related: 'rental',
        maintenance: 'service',
        leases: 'rental',
    };
    const normalized = requested ? (legacyTabs[requested] ?? requested) : null;

    return available.includes(normalized as TenantDetailTab)
        ? (normalized as TenantDetailTab)
        : 'overview';
}

export function TenantDetailTabs({
    tabs,
    active,
    onSelect,
}: {
    tabs: TenantTab[];
    active: TenantDetailTab;
    onSelect: (tab: TenantDetailTab) => void;
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
            className="pmc-tenant-detail-tabs"
            role="tablist"
            aria-orientation="horizontal"
            aria-label={t('tenants.detail_navigation')}
            data-testid="tenant-detail-tabs"
        >
            {tabs.map((tab, index) => (
                <button
                    type="button"
                    role="tab"
                    id={`tenant-tab-${tab.key}`}
                    aria-controls="tenant-detail-panel"
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
                    <span>{t(`tenants.tabs.${tab.key}`)}</span>
                    {tab.count !== null ? <strong>{tab.count}</strong> : null}
                </button>
            ))}
        </div>
    );
}

function tabCount(
    detail: TenantDetailPage,
    tab: TenantDetailTab,
): number | null {
    const relatedKeys: Partial<Record<TenantDetailTab, TenantRelatedKey>> = {
        rental: 'leases',
        payments: 'payments',
        service: 'maintenance',
    };

    if (tab === 'documents') {
        return detail.documents.length;
    }

    if (tab === 'history') {
        return detail.timeline.length;
    }

    const relatedKey = relatedKeys[tab];

    if (!relatedKey) {
        return null;
    }

    return (
        detail.related.find((table) => table.key === relatedKey)?.rows.length ??
        0
    );
}
