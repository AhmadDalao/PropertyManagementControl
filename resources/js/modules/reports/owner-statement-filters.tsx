import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { ReportFilters } from './report-filters';
import { cleanReportFilters } from './report-query';
import type {
    OwnerStatementPageProps,
    OwnerStatementTab,
    ReportFilterValues,
} from './types';

export function OwnerStatementFilters({
    props,
    activeTab,
}: {
    props: OwnerStatementPageProps;
    activeTab: OwnerStatementTab;
}) {
    const [filters, setFilters] = useState<ReportFilterValues>({
        period: props.filters.period,
        date_from: props.filters.date_from,
        date_to: props.filters.date_to,
        portfolio_id: props.filters.portfolio_id
            ? String(props.filters.portfolio_id)
            : 'all',
        property_id: props.filters.property_id
            ? String(props.filters.property_id)
            : 'all',
    });
    const [filtersOpen, setFiltersOpen] = useState(false);
    const resetQuery = props.filters.portfolio_id
        ? `?portfolio_id=${props.filters.portfolio_id}`
        : '';

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(
            '/reports/statement',
            {
                ...cleanReportFilters(filters),
                tab: activeTab,
            },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };

    return (
        <ReportFilters
            filters={filters}
            filtersOpen={filtersOpen}
            mode={props.mode}
            portfolioOptions={props.portfolioOptions}
            propertyOptions={props.propertyOptions}
            propertyContext={props.propertyContext}
            resetHref={`/reports/statement${resetQuery}`}
            onChange={setFilters}
            onSubmit={applyFilters}
            onToggle={() => setFiltersOpen((open) => !open)}
        />
    );
}
