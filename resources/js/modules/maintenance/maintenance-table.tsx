import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import {
    RecordActions,
    StatusBadge,
    humanLabel,
} from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import { maintenanceFilterFields } from './maintenance-filters';
import type { MaintenanceIndexPageProps } from './types';

type MaintenanceTableProps = Pick<
    MaintenanceIndexPageProps,
    | 'mode'
    | 'requests'
    | 'filters'
    | 'counts'
    | 'categoryOptions'
    | 'priorityOptions'
    | 'statusOptions'
    | 'app'
>;

export function MaintenanceTable(props: MaintenanceTableProps) {
    const { locale, t, text } = useTranslator();
    const filterFields = maintenanceFilterFields({
        categories: props.categoryOptions,
        priorities: props.priorityOptions,
        statuses: props.statusOptions,
    });

    return (
        <DataTable
            title="Maintenance queue"
            description="Search request, category, asset, tenant, or assignee."
            data={props.requests}
            filters={props.filters}
            counts={props.counts}
            basePath="/maintenance-requests"
            rowHref={(request) => `/maintenance-requests/${request.id}`}
            exportHref={
                props.mode === 'manager'
                    ? exportUrl('/exports/maintenance-requests', props.filters)
                    : undefined
            }
            filterFields={filterFields}
            columns={[
                {
                    key: 'request',
                    label: 'Request',
                    render: (request) => (
                        <div className="pmc-primary-cell">
                            <strong>
                                #{request.id} {request.title}
                            </strong>
                            <span>{text(humanLabel(request.category))}</span>
                            {request.is_overdue ? (
                                <StatusBadge value="overdue" tone="danger" />
                            ) : null}
                        </div>
                    ),
                },
                {
                    key: 'asset',
                    label: 'Asset / tenant',
                    render: (request) => (
                        <div className="pmc-stacked-cell">
                            <strong>
                                {(locale === 'ar'
                                    ? request.asset?.title_ar ||
                                      request.asset?.title_en
                                    : request.asset?.title_en ||
                                      request.asset?.title_ar) ??
                                    text('No asset')}
                            </strong>
                            <span>
                                {request.tenant_profile?.user?.name ??
                                    text('No tenant')}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'assignment',
                    label: 'Assignment',
                    render: (request) => (
                        <div className="pmc-stacked-cell">
                            <strong>
                                {request.assigned_to?.name ??
                                    text('Unassigned')}
                            </strong>
                            <span>
                                {props.mode === 'manager'
                                    ? `${currency(
                                          request.expense_total,
                                          props.app.locale,
                                      )} ${t(
                                          'maintenance.cost_entries',
                                          undefined,
                                          { count: request.expense_count },
                                      )}`
                                    : text('Owner or manager assignment')}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'priority',
                    label: 'Priority',
                    render: (request) => (
                        <StatusBadge
                            value={request.priority}
                            tone={
                                request.priority === 'urgent'
                                    ? 'danger'
                                    : request.priority === 'high'
                                      ? 'warning'
                                      : 'neutral'
                            }
                        />
                    ),
                },
                {
                    key: 'status',
                    label: 'Status',
                    render: (request) => (
                        <div className="pmc-stacked-cell">
                            <StatusBadge value={request.status} />
                            <span>
                                {text('Due')}{' '}
                                {humanDate(request.due_at, props.app.locale)}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'created',
                    label: 'Created',
                    render: (request) =>
                        humanDate(request.created_at, props.app.locale),
                },
                {
                    key: 'actions',
                    label: 'Actions',
                    className: 'text-end',
                    render: (request) => (
                        <RecordActions
                            showHref={`/maintenance-requests/${request.id}`}
                            editHref={
                                props.mode === 'manager'
                                    ? `/maintenance-requests/${request.id}/edit`
                                    : undefined
                            }
                        >
                            {!['cancelled', 'resolved'].includes(
                                request.status,
                            ) ? (
                                <ArchiveAction
                                    href={`/maintenance-requests/${request.id}`}
                                    label="Cancel"
                                    confirmMessage={t(
                                        'maintenance.cancel_confirm',
                                        undefined,
                                        { id: request.id },
                                    )}
                                />
                            ) : null}
                        </RecordActions>
                    ),
                },
            ]}
        />
    );
}
