import type { ReportFilterValues } from './types';

export function cleanReportFilters(filters: ReportFilterValues) {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([, value]) => value !== '' && value !== 'all',
        ),
    );
}
