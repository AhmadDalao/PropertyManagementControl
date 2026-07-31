import { router } from '@inertiajs/react';
import { useEffect, useEffectEvent, useState } from 'react';

import type { PropertyExplorerFilters } from './types';

type FilterPatch = Partial<
    Omit<PropertyExplorerFilters, 'property_id' | 'node_id'>
> & {
    property_id?: number | null;
    node_id?: number | null;
};

export function useExplorerQuery(filters: PropertyExplorerFilters) {
    const [search, setSearch] = useState(filters.search);
    const [pending, setPending] = useState(false);

    function visit(patch: FilterPatch) {
        const next = { ...filters, ...patch };
        const query: Record<string, string | number> = {};

        for (const [key, value] of Object.entries(next)) {
            if (value === null || value === '' || value === 'all') {
                continue;
            }

            query[key] = value;
        }

        router.get('/property-explorer', query, {
            only: ['explorer', 'propertyContext'],
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onStart: () => setPending(true),
            onFinish: () => setPending(false),
        });
    }

    const runSearch = useEffectEvent((value: string) => {
        visit({ search: value, page: 1 });
    });

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timer = window.setTimeout(() => runSearch(search), 400);

        return () => window.clearTimeout(timer);
    }, [filters.search, search]);

    const selectProperty = (propertyId: number) => {
        setSearch('');
        visit({
            property_id: propertyId,
            node_id: null,
            search: '',
            asset_type: 'all',
            occupancy_status: 'all',
            page: 1,
        });
    };

    const reset = () => {
        setSearch('');
        visit({
            node_id: filters.property_id,
            search: '',
            asset_type: 'all',
            occupancy_status: 'all',
            page: 1,
        });
    };

    return { pending, reset, search, selectProperty, setSearch, visit };
}
