import { router } from '@inertiajs/react';
import { useState } from 'react';

import type { Translator } from '@/lib/i18n';

import type {
    WordingEntry,
    WordingFilterOverrides,
    WordingFilters,
    WordingTab,
} from './types';
import { wordingGroupLabel } from './wording-labels';

export function useWordingWorkspace(filters: WordingFilters, t: Translator) {
    const [tab, setTab] = useState<WordingTab>('wording');
    const [selected, setSelected] = useState<WordingEntry | null>(null);
    const [search, setSearch] = useState(filters.search);

    const applyFilters = (overrides: WordingFilterOverrides = {}) => {
        router.get(
            '/wording',
            {
                search,
                group: filters.group,
                state: filters.state,
                per_page: filters.perPage,
                content_module: filters.contentModule,
                ...overrides,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return {
        applyFilters,
        closeEditor: () => setSelected(null),
        groupLabel: (group: string) => wordingGroupLabel(group, t),
        search,
        selected,
        selectEntry: setSelected,
        setSearch,
        setTab,
        tab,
    };
}
