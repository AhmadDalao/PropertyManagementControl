import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import { ActionCenterFilterControls } from './action-center-filter-controls';
import { ActionCenterTypeChips } from './action-center-type-chips';
import type {
    ActionCenterFilters as FilterState,
    ActionCenterPageProps,
} from './types';

type FilterProps = Pick<
    ActionCenterPageProps,
    | 'assigneeOptions'
    | 'auth'
    | 'counts'
    | 'filters'
    | 'portfolioOptions'
    | 'propertyOptions'
>;

export function ActionCenterFilters(props: FilterProps) {
    return (
        <ActionCenterFilterSession
            key={JSON.stringify(props.filters)}
            {...props}
        />
    );
}

function ActionCenterFilterSession(props: FilterProps) {
    const { t } = useTranslator();
    const [draft, setDraft] = useState<FilterState>(props.filters);
    const [filtersOpen, setFiltersOpen] = useState(false);
    const activeFilters = countActiveFilters(props.filters);

    return (
        <section className="pmc-action-filter-shell">
            <ActionCenterTypeChips
                counts={props.counts}
                filters={props.filters}
            />

            <button
                type="button"
                className="pmc-action-mobile-filter"
                aria-expanded={filtersOpen}
                aria-controls="action-center-filter-form"
                onClick={() => setFiltersOpen((open) => !open)}
            >
                <i className="bi bi-sliders2" aria-hidden="true" />
                <span>{t('action_center.filters')}</span>
                {activeFilters > 0 ? <strong>{activeFilters}</strong> : null}
            </button>

            <ActionCenterFilterControls
                {...props}
                draft={draft}
                filtersOpen={filtersOpen}
                setDraft={setDraft}
            />
        </section>
    );
}

function countActiveFilters(filters: FilterState): number {
    return [
        filters.search !== '',
        filters.type !== 'all',
        filters.priority !== 'all',
        filters.assignee !== 'all',
        filters.portfolio_id !== null,
        filters.property_id !== null,
    ].filter(Boolean).length;
}
