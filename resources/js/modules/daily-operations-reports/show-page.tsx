import { Head, usePage } from '@inertiajs/react';

import { MetricGrid, WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { dateTime, humanDate } from '@/lib/utils';

import '../../../css/styles/daily-operations-reports.css';
import { ReportDetailPanels } from './report-detail-panels';
import type { DailyReportShowProps } from './types';

export default function DailyOperationsReportShowPage() {
    const { props } = usePage<DailyReportShowProps>();
    const { report } = props;
    const { locale, t } = useTranslator();
    const priority = report.summary.priority;

    return (
        <AdminLayout>
            <Head
                title={t('daily_reports.report_number', undefined, {
                    id: report.id,
                })}
            />
            <WorkspaceHeader
                eyebrow={t('daily_reports.detail_eyebrow')}
                title={`${report.scope_label} · ${humanDate(report.report_date, locale)}`}
                description={t('daily_reports.detail_description')}
                actions={[
                    {
                        label: t('daily_reports.title'),
                        href: '/reports/daily-operations',
                        icon: 'bi-arrow-left',
                        tone: 'quiet',
                    },
                    {
                        label: t('daily_reports.open_action_center'),
                        href: report.action_center_url,
                        icon: 'bi-list-check',
                        tone: 'primary',
                    },
                ]}
            />

            <section className="pmc-daily-report-detail-status">
                <div>
                    <span
                        className={`pmc-daily-report-status is-${report.status}`}
                    >
                        {report.status_label}
                    </span>
                    <strong>{report.trigger_label}</strong>
                </div>
                <p>
                    {report.completed_at
                        ? dateTime(report.completed_at, locale)
                        : t('daily_reports.pending')}
                </p>
            </section>

            <MetricGrid
                metrics={[
                    {
                        label: t('daily_reports.actions'),
                        value: (priority?.total ?? 0).toLocaleString(locale),
                        detail: t('daily_reports.priority_position'),
                        icon: 'bi-list-check',
                        tone: 'ink',
                    },
                    {
                        label: t('daily_reports.critical'),
                        value: (priority?.critical ?? 0).toLocaleString(locale),
                        detail: t('daily_reports.priority_position'),
                        icon: 'bi-exclamation-octagon',
                        tone: 'red',
                    },
                    {
                        label: t('daily_reports.high'),
                        value: (priority?.high ?? 0).toLocaleString(locale),
                        detail: t('daily_reports.priority_position'),
                        icon: 'bi-exclamation-triangle',
                        tone: 'amber',
                    },
                    {
                        label: t('daily_reports.unassigned'),
                        value: (priority?.unassigned ?? 0).toLocaleString(
                            locale,
                        ),
                        detail: t('daily_reports.priority_position'),
                        icon: 'bi-person-slash',
                        tone: 'blue',
                    },
                ]}
            />

            {report.failure_summary ? (
                <p className="pmc-daily-report-alert" role="alert">
                    <i
                        className="bi bi-exclamation-triangle"
                        aria-hidden="true"
                    />
                    {report.failure_summary}
                </p>
            ) : null}

            <ReportDetailPanels report={report} />
        </AdminLayout>
    );
}
