import { useRef } from 'react';
import type { KeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { VendorDetailPage, VendorDetailTab } from './types';

type VendorTab = { key: VendorDetailTab; count: number | null };

export function vendorTabs(detail: VendorDetailPage): VendorTab[] {
    return detail.availableTabs.map((key) => ({
        key,
        count:
            key === 'workload'
                ? detail.workload.open.length + detail.workload.history.length
                : key === 'history'
                  ? detail.timeline.length
                  : null,
    }));
}

export function requestedVendorTab(
    available: VendorDetailTab[],
): VendorDetailTab {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URL(window.location.href).searchParams.get('tab');

    return available.includes(requested as VendorDetailTab)
        ? (requested as VendorDetailTab)
        : 'overview';
}

export function VendorDetailTabs({
    tabs,
    active,
    onSelect,
}: {
    tabs: VendorTab[];
    active: VendorDetailTab;
    onSelect: (tab: VendorDetailTab) => void;
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
            className="pmc-vendor-detail-tabs"
            role="tablist"
            aria-label={t('maintenance_vendors.detail_navigation')}
            data-testid="vendor-detail-tabs"
        >
            {tabs.map((tab, index) => (
                <button
                    type="button"
                    role="tab"
                    id={`vendor-tab-${tab.key}`}
                    aria-controls="vendor-detail-panel"
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
                    <span>{t(`maintenance_vendors.tabs.${tab.key}`)}</span>
                    {tab.count !== null ? <strong>{tab.count}</strong> : null}
                </button>
            ))}
        </div>
    );
}
