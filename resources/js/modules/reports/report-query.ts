import type { ReportFilterValues } from './types';

export function cleanReportFilters(filters: ReportFilterValues) {
    return Object.fromEntries(
        Object.entries(filters).filter(([key, value]) => {
            if (value === '' || value === 'all') {
                return false;
            }

            return (
                filters.period === 'custom' ||
                !['date_from', 'date_to'].includes(key)
            );
        }),
    );
}
