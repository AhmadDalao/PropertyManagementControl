import { router } from '@inertiajs/react';
import { useState } from 'react';

import type { TableFilters } from '@/types';

import {
    cleanFilters,
    IGNORED_ACTIVE_FILTERS,
    stringifyFilters,
} from './table-utils';
import type { TableVisit } from './types';

export function useTableQuery({
    filters,
    basePath,
}: {
    filters: TableFilters;
    basePath: string;
}) {
    const [draftFilters, setDraftFilters] = useState<Record<string, string>>(
        stringifyFilters(filters),
    );
    const [filtersOpen, setFiltersOpen] = useState(false);
    const activeFilters = Object.entries(draftFilters).filter(
        ([key, value]) =>
            !IGNORED_ACTIVE_FILTERS.has(key) && value !== '' && value !== 'all',
    );

    const visit: TableVisit = (overrides = {}) => {
        const nextFilters = {
            ...draftFilters,
            ...stringifyFilters(overrides),
        };

        if (!Object.prototype.hasOwnProperty.call(overrides, 'page')) {
            nextFilters.page = '1';
        }

        setDraftFilters(nextFilters);
        router.get(basePath, cleanFilters(nextFilters), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const reset = () => {
        const resetFilters = { per_page: '10' };
        setDraftFilters(resetFilters);
        router.get(basePath, {}, { preserveScroll: true, replace: true });
    };

    const removeFilter = (name: string) => {
        visit({ [name]: 'all' });
    };

    return {
        activeFilters,
        draftFilters,
        filtersOpen,
        removeFilter,
        reset,
        setDraftFilters,
        setFiltersOpen,
        visit,
    };
}
