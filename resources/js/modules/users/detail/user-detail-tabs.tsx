import { useRef } from 'react';
import type { KeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { UserDetailPage, UserDetailTab, UserRelatedKey } from './types';

type UserTab = { key: UserDetailTab; count: number | null };

export function userTabs(detail: UserDetailPage): UserTab[] {
    return detail.availableTabs.map((key) => ({
        key,
        count: tabCount(detail, key),
    }));
}

export function requestedUserTab(available: UserDetailTab[]): UserDetailTab {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URL(window.location.href).searchParams.get('tab');
    const legacyTabs: Record<string, UserDetailTab> = {
        related: 'properties',
        assets: 'properties',
        maintenance: 'workload',
        security: 'access',
    };
    const normalized = requested ? (legacyTabs[requested] ?? requested) : null;

    return available.includes(normalized as UserDetailTab)
        ? (normalized as UserDetailTab)
        : 'overview';
}

export function UserDetailTabs({
    tabs,
    active,
    onSelect,
}: {
    tabs: UserTab[];
    active: UserDetailTab;
    onSelect: (tab: UserDetailTab) => void;
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
            className="pmc-user-detail-tabs"
            role="tablist"
            aria-orientation="horizontal"
            aria-label={t('users.detail_navigation')}
            data-testid="user-detail-tabs"
        >
            {tabs.map((tab, index) => (
                <button
                    type="button"
                    role="tab"
                    id={`user-tab-${tab.key}`}
                    aria-controls="user-detail-panel"
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
                    <span>{t(`users.tabs.${tab.key}`)}</span>
                    {tab.count !== null ? <strong>{tab.count}</strong> : null}
                </button>
            ))}
        </div>
    );
}

function tabCount(detail: UserDetailPage, tab: UserDetailTab): number | null {
    const relatedKeys: Partial<Record<UserDetailTab, UserRelatedKey>> = {
        properties: 'properties',
        workload: 'workload',
    };

    if (tab === 'documents') {
        return detail.documents.length;
    }

    if (tab === 'history') {
        return detail.timeline.length;
    }

    const relatedKey = relatedKeys[tab];

    return relatedKey
        ? (detail.related.find((table) => table.key === relatedKey)?.rows
              .length ?? 0)
        : null;
}
