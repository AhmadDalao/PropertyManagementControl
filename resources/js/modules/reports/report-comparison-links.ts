import type { ReportComparisonMetric, ReportDataProps } from './types';

type ReportFilters = ReportDataProps['filters'];
type ComparisonPeriod = ReportDataProps['comparison']['period'];

export function comparisonMetricHref(
    metric: ReportComparisonMetric['key'],
    filters: ReportFilters,
): string {
    const query = scopeQuery(filters, {
        date_from: filters.date_from,
        date_to: filters.date_to,
    });

    if (metric === 'collected') {
        query.set('status', 'posted');

        return `/payments?${query}`;
    }

    if (metric === 'expenses') {
        query.set('status', 'posted');

        return `/expenses?${query}`;
    }

    if (metric === 'maintenance_opened') {
        return `/maintenance-requests?${query}`;
    }

    if (metric === 'maintenance_resolved') {
        query.set('status', 'resolved');

        return `/maintenance-requests?${query}`;
    }

    if (metric === 'net_position') {
        query.set('period', 'custom');
        query.set('tab', 'costs');

        return `/reports?${query}`;
    }

    return `/rent-collection?${query}`;
}

export function previousComparisonHref(
    filters: ReportFilters,
    period: ComparisonPeriod,
): string {
    const query = scopeQuery(filters, period);
    query.set('period', 'custom');
    query.set('tab', 'overview');

    return `/reports?${query}`;
}

function scopeQuery(
    filters: ReportFilters,
    period: ComparisonPeriod,
): URLSearchParams {
    const query = new URLSearchParams({
        date_from: period.date_from,
        date_to: period.date_to,
    });

    if (filters.portfolio_id) {
        query.set('portfolio_id', String(filters.portfolio_id));
    }

    if (filters.property_id) {
        query.set('property_id', String(filters.property_id));
    }

    return query;
}
