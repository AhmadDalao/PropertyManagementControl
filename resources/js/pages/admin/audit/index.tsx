import { Head, Link, usePage } from '@inertiajs/react';

import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type AuditRecord = {
    id: number;
    event: string;
    description: string;
    subject_type?: string | null;
    subject_type_label: string;
    subject_label: string;
    causer_label: string;
    changed_keys: string[];
    created_at?: string | null;
};

type PageProps = SharedProps & {
    activities: PaginatedData<AuditRecord>;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    subjectTypeOptions: Array<{ label: string; value: string }>;
    causerOptions: Array<{ id: number; name: string }>;
};

const eventOptions = [
    { label: 'All', value: 'all' },
    { label: 'Created', value: 'created' },
    { label: 'Updated', value: 'updated' },
    { label: 'Deleted', value: 'deleted' },
];

export default function AuditLogsPage() {
    const { props } = usePage<PageProps>();
    const isSuperadmin = Boolean(props.auth.user?.roles.includes('superadmin'));
    const filterFields: TableFilterField[] = [
        {
            name: 'event',
            label: 'Event',
            options: eventOptions,
        },
        {
            name: 'subject_type',
            label: 'Subject',
            options: [
                { label: 'All', value: 'all' },
                ...props.subjectTypeOptions,
            ],
        },
        {
            name: 'causer_id',
            label: 'Changed by',
            options: [
                { label: 'All', value: 'all' },
                ...props.causerOptions.map((user) => ({
                    label: user.name,
                    value: user.id,
                })),
            ],
        },
        { name: 'date_from', label: 'From', type: 'date' },
        { name: 'date_to', label: 'To', type: 'date' },
    ];

    if (isSuperadmin) {
        filterFields.push({
            name: 'portfolio_id',
            label: 'Portfolio',
            options: [
                { label: 'All', value: 'all' },
                ...props.portfolioOptions.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return (
        <AdminLayout>
            <Head title="Audit History" />
            <PageHeader
                title="Audit History"
                description="Trace who changed operational records, when it happened, and which fields were touched."
                actions={
                    <>
                        <Link
                            href="/documentation"
                            className="btn btn-outline-secondary"
                        >
                            Audit guide
                        </Link>
                        <a
                            href={exportUrl(
                                '/audit-logs/export',
                                props.filters,
                            )}
                            className="btn btn-primary"
                        >
                            <i className="bi bi-download me-2" />
                            Export Excel (.xlsx)
                        </a>
                    </>
                }
            />

            <div className="pmc-card p-4 mb-4 pmc-card--teal">
                <div className="row g-3 align-items-center">
                    <div className="col-lg-8">
                        <div className="pmc-kicker mb-2">Traceability</div>
                        <h2 className="h4 mb-2">
                            History should answer “who touched this?” without
                            digging through the database.
                        </h2>
                        <p className="mb-0 text-secondary">
                            This screen shows field names only, not raw old/new
                            values, so sensitive changes stay traceable without
                            dumping secrets into the UI.
                        </p>
                    </div>
                    <div className="col-lg-4">
                        <div className="pmc-audit-summary">
                            <span>{props.activities.total}</span>
                            <strong>tracked events in this view</strong>
                        </div>
                    </div>
                </div>
            </div>

            <DataTable
                title="Activity log"
                description="Search descriptions, filter event type, subject, actor, portfolio, and date range."
                data={props.activities}
                filters={props.filters}
                counts={props.counts}
                basePath="/audit-logs"
                exportHref={exportUrl('/audit-logs/export', props.filters)}
                filterFields={filterFields}
                emptyText="No activity events match this audit view."
                columns={[
                    {
                        key: 'event',
                        label: 'Event',
                        render: (activity) => (
                            <div>
                                <span
                                    className={`pmc-chip ${eventTone(activity.event)}`}
                                >
                                    {prettyLabel(activity.event)}
                                </span>
                                <div className="small text-secondary mt-2">
                                    {formatDateTime(activity.created_at)}
                                </div>
                            </div>
                        ),
                    },
                    {
                        key: 'subject',
                        label: 'Subject',
                        render: (activity) => (
                            <div>
                                <div className="fw-semibold">
                                    {activity.subject_label}
                                </div>
                                <div className="small text-secondary">
                                    {activity.subject_type_label}
                                </div>
                            </div>
                        ),
                    },
                    {
                        key: 'causer',
                        label: 'Changed by',
                        render: (activity) => (
                            <div>
                                <div className="fw-semibold">
                                    {activity.causer_label}
                                </div>
                                <div className="small text-secondary">
                                    #{activity.id}
                                </div>
                            </div>
                        ),
                    },
                    {
                        key: 'changes',
                        label: 'Fields',
                        render: (activity) =>
                            activity.changed_keys.length > 0 ? (
                                <div className="d-flex gap-1 flex-wrap">
                                    {activity.changed_keys
                                        .slice(0, 5)
                                        .map((key) => (
                                            <span
                                                className="pmc-chip"
                                                key={`${activity.id}-${key}`}
                                            >
                                                {prettyLabel(key)}
                                            </span>
                                        ))}
                                    {activity.changed_keys.length > 5 ? (
                                        <span className="pmc-chip">
                                            +{activity.changed_keys.length - 5}
                                        </span>
                                    ) : null}
                                </div>
                            ) : (
                                <span className="text-secondary">
                                    No field diff
                                </span>
                            ),
                    },
                    {
                        key: 'description',
                        label: 'Description',
                        render: (activity) => (
                            <span className="text-break">
                                {activity.description}
                            </span>
                        ),
                    },
                ]}
            />
        </AdminLayout>
    );
}

function prettyLabel(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function eventTone(event: string): string {
    if (event === 'created') {
        return 'pmc-chip--teal';
    }

    if (event === 'deleted') {
        return 'pmc-chip--primary';
    }

    return '';
}

function formatDateTime(value?: string | null): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('en', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}
