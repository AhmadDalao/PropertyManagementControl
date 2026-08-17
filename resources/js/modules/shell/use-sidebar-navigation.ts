import { useState } from 'react';

import type { ModuleNavGroup } from '@/modules/registry';

import { isActivePath } from './navigation-access';

const GROUP_STORAGE_KEY = 'property-sidebar-group';

export function useSidebarNavigation(
    groups: ModuleNavGroup[],
    currentUrl: string,
    compact: boolean,
    onExpand: () => void,
) {
    const activeGroupKey = groups.find((group) =>
        group.items.some((item) => isActivePath(currentUrl, item.href)),
    )?.labelKey;
    const [openGroupKey, setOpenGroupKey] = useState<string | null>(() =>
        initialGroupKey(groups, activeGroupKey),
    );

    function toggleGroup(groupKey: string) {
        const nextKey = openGroupKey === groupKey && !compact ? null : groupKey;

        setOpenGroupKey(nextKey);
        writeGroupPreference(nextKey);

        if (compact) {
            onExpand();
        }
    }

    return {
        activeGroupKey,
        openGroupKey: compact ? null : openGroupKey,
        toggleGroup,
    };
}

function initialGroupKey(
    groups: ModuleNavGroup[],
    activeGroupKey?: string,
): string | null {
    if (activeGroupKey) {
        return activeGroupKey;
    }

    const stored = readGroupPreference();

    if (stored && groups.some((group) => group.labelKey === stored)) {
        return stored;
    }

    return groups[0]?.labelKey ?? null;
}

function readGroupPreference(): string | null {
    try {
        return typeof window === 'undefined'
            ? null
            : window.localStorage.getItem(GROUP_STORAGE_KEY);
    } catch {
        return null;
    }
}

function writeGroupPreference(groupKey: string | null): void {
    try {
        if (groupKey) {
            window.localStorage.setItem(GROUP_STORAGE_KEY, groupKey);
        } else {
            window.localStorage.removeItem(GROUP_STORAGE_KEY);
        }
    } catch {
        // Navigation remains usable when browser storage is unavailable.
    }
}
