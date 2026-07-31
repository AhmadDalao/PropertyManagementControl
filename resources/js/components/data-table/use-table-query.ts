import { router } from '@inertiajs/react';
import { useEffect, useEffectEvent, useRef, useState } from 'react';

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
    const [isSearching, setIsSearching] = useState(false);
    const submittedSearch = useRef(String(filters.search ?? ''));
    const searchRequest = useRef(0);
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
        submittedSearch.current = nextFilters.search ?? '';
        searchRequest.current += 1;
        setIsSearching(false);
        router.get(basePath, cleanFilters(nextFilters), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const runLiveSearch = useEffectEvent((search: string) => {
        if (search === submittedSearch.current) {
            return;
        }

        submittedSearch.current = search;
        const request = searchRequest.current + 1;
        searchRequest.current = request;
        const nextFilters = { ...draftFilters, search, page: '1' };
        setIsSearching(true);

        router.get(basePath, cleanFilters(nextFilters), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onFinish: () => {
                if (searchRequest.current === request) {
                    setIsSearching(false);
                }
            },
        });
    });

    useEffect(() => {
        const search = draftFilters.search ?? '';
        const appliedSearch = String(filters.search ?? '');

        if (search === appliedSearch) {
            submittedSearch.current = search;

            return;
        }

        const timeout = window.setTimeout(() => runLiveSearch(search), 400);

        return () => window.clearTimeout(timeout);
    }, [draftFilters.search, filters.search]);

    const reset = () => {
        const resetFilters = { per_page: '10' };
        setDraftFilters(resetFilters);
        submittedSearch.current = '';
        searchRequest.current += 1;
        setIsSearching(false);
        router.get(basePath, {}, { preserveScroll: true, replace: true });
    };

    const removeFilter = (name: string) => {
        visit({ [name]: 'all' });
    };

    return {
        activeFilters,
        draftFilters,
        filtersOpen,
        isSearching,
        removeFilter,
        reset,
        setDraftFilters,
        setFiltersOpen,
        visit,
    };
}
