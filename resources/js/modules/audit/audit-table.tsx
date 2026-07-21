import { Link } from '@inertiajs/react';

import { DataTable, exportUrl } from '@/components/data-table';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { auditFilterFields } from './audit-filters';
import { auditEventTone, formatAuditDate } from './audit-format';
import type { AuditIndexPageProps, AuditRecord } from './types';

export function AuditTable({ props }: { props: AuditIndexPageProps }) {
    const { locale, t } = useTranslator();

    return (
        <DataTable
            title={t('audit.register_title')}
            description={t('audit.register_description')}
            data={props.activities}
            filters={props.filters}
            counts={props.counts}
            basePath="/audit-logs"
            exportHref={exportUrl('/audit-logs/export', props.filters)}
            filterFields={auditFilterFields(props, t)}
            emptyText={t('audit.no_matches')}
            mobileCard={{
                title: (activity) => activity.subject_label,
                subtitle: (activity) =>
                    activity.description || t('audit.no_description'),
                status: (activity) => <AuditEvent activity={activity} />,
                meta: [
                    {
                        label: t('audit.changed_by'),
                        value: (activity) => activity.causer_label,
                    },
                    {
                        label: t('audit.changed_fields'),
                        value: (activity) =>
                            t('audit.fields_changed', undefined, {
                                count: activity.changed_count,
                            }),
                    },
                    {
                        label: t('audit.recorded_at'),
                        value: (activity) =>
                            formatAuditDate(activity.created_at, locale),
                    },
                ],
                actions: (activity) => <AuditAction activity={activity} />,
            }}
            columns={[
                {
                    key: 'event',
                    label: t('audit.event'),
                    render: (activity) => (
                        <div className="pmc-stacked-cell">
                            <AuditEvent activity={activity} />
                            <span>
                                {formatAuditDate(activity.created_at, locale)}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'subject',
                    label: t('audit.subject'),
                    render: (activity) => (
                        <div className="pmc-stacked-cell">
                            <strong>{activity.subject_label}</strong>
                            <span>{activity.subject_type_label}</span>
                            <small>
                                {activity.description ||
                                    t('audit.no_description')}
                            </small>
                        </div>
                    ),
                },
                {
                    key: 'causer',
                    label: t('audit.changed_by'),
                    render: (activity) => (
                        <div className="pmc-stacked-cell">
                            <strong>{activity.causer_label}</strong>
                            <span>
                                {t('audit.event_number', undefined, {
                                    id: activity.id,
                                })}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'changes',
                    label: t('audit.changed_fields'),
                    render: (activity) => <ChangedFields activity={activity} />,
                },
                {
                    key: 'actions',
                    label: t('audit.actions'),
                    className: 'text-end',
                    render: (activity) => <AuditAction activity={activity} />,
                },
            ]}
        />
    );
}

function AuditEvent({ activity }: { activity: AuditRecord }) {
    return (
        <StatusBadge
            value={activity.event}
            label={activity.event_label}
            tone={auditEventTone(activity.event)}
        />
    );
}

function ChangedFields({ activity }: { activity: AuditRecord }) {
    const { t } = useTranslator();

    if (activity.changed_keys.length === 0) {
        return (
            <span className="text-secondary">{t('audit.no_field_diff')}</span>
        );
    }

    return (
        <div className="d-flex gap-1 flex-wrap">
            {activity.changed_keys.slice(0, 3).map((key) => (
                <span
                    className="pmc-chip font-monospace text-body"
                    dir="ltr"
                    key={`${activity.id}-${key}`}
                >
                    {key}
                </span>
            ))}
            {activity.changed_keys.length > 3 ? (
                <span className="pmc-chip">
                    +{activity.changed_keys.length - 3}
                </span>
            ) : null}
        </div>
    );
}

function AuditAction({ activity }: { activity: AuditRecord }) {
    const { t } = useTranslator();

    return activity.subject_url ? (
        <Link href={activity.subject_url} className="pmc-record-open">
            {t('audit.open_subject')}
            <i className="bi bi-arrow-up-right" />
        </Link>
    ) : (
        <span className="text-secondary small">
            {t('audit.record_unavailable')}
        </span>
    );
}
