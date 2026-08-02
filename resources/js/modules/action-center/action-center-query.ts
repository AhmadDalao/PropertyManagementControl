import type { ActionCenterFilters } from './types';

export function actionCenterUrl(
    filters: ActionCenterFilters,
    patch: Partial<ActionCenterFilters> = {},
): string {
    const next = {
        ...filters,
        ...patch,
    };
    const params = new URLSearchParams();

    if (next.search) {
        params.set('search', next.search);
    }

    if (next.type !== 'all') {
        params.set('type', next.type);
    }

    if (next.priority !== 'all') {
        params.set('priority', next.priority);
    }

    if (next.assignee !== 'all') {
        params.set('assignee', next.assignee);
    }

    if (next.portfolio_id) {
        params.set('portfolio_id', String(next.portfolio_id));
    }

    if (next.property_id) {
        params.set('property_id', String(next.property_id));
    }

    if (next.per_page !== 12) {
        params.set('per_page', String(next.per_page));
    }

    if (next.page > 1) {
        params.set('page', String(next.page));
    }

    const query = params.toString();

    return query ? `/action-center?${query}` : '/action-center';
}

export function actionCenterExportUrl(filters: ActionCenterFilters): string {
    return actionCenterDownloadUrl(filters, '/action-center/export');
}

export function actionCenterReportUrl(
    filters: ActionCenterFilters,
    format: 'pdf' | 'docx' | 'xlsx',
): string {
    return format === 'xlsx'
        ? actionCenterExportUrl(filters)
        : actionCenterDownloadUrl(filters, `/action-center/report.${format}`);
}

function actionCenterDownloadUrl(
    filters: ActionCenterFilters,
    path: string,
): string {
    return actionCenterUrl({
        ...filters,
        per_page: 12,
        page: 1,
    }).replace('/action-center', path);
}
