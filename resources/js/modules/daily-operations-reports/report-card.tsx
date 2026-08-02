import { Link, router } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { dateTime, humanDate } from '@/lib/utils';

import type { DailyReportRecord } from './types';

function bytes(value: number, locale: string) {
    if (value <= 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const unit = Math.min(Math.floor(Math.log(value) / Math.log(1024)), 3);

    return `${new Intl.NumberFormat(locale === 'ar' ? 'ar-SA' : 'en', {
        maximumFractionDigits: 1,
    }).format(value / 1024 ** unit)} ${units[unit]}`;
}

export function ReportCard({ report }: { report: DailyReportRecord }) {
    const { locale, t } = useTranslator();
    const priority = report.summary.priority;
    const finishedAt =
        report.completed_at ?? report.failed_at ?? report.started_at;
    const formats = ['pdf', 'docx', 'xlsx'] as const;

    const prune = () => {
        if (!window.confirm(t('daily_reports.prune_confirm'))) {
            return;
        }

        router.delete(report.prune_url, { preserveScroll: true });
    };

    return (
        <article className={`pmc-daily-report-card is-${report.status}`}>
            <header>
                <div>
                    <span>
                        {t('daily_reports.report_number', undefined, {
                            id: report.id,
                        })}
                    </span>
                    <h3>{report.scope_label}</h3>
                    <p>{humanDate(report.report_date, locale)}</p>
                </div>
                <span className={`pmc-daily-report-status is-${report.status}`}>
                    {report.status_label}
                </span>
            </header>

            <dl className="pmc-daily-report-facts">
                <div>
                    <dt>{t('daily_reports.actions')}</dt>
                    <dd>{report.item_count.toLocaleString(locale)}</dd>
                </div>
                <div>
                    <dt>{t('daily_reports.critical')}</dt>
                    <dd>{(priority?.critical ?? 0).toLocaleString(locale)}</dd>
                </div>
                <div>
                    <dt>{t('daily_reports.high')}</dt>
                    <dd>{(priority?.high ?? 0).toLocaleString(locale)}</dd>
                </div>
                <div>
                    <dt>{t('daily_reports.unassigned')}</dt>
                    <dd>
                        {(priority?.unassigned ?? 0).toLocaleString(locale)}
                    </dd>
                </div>
            </dl>

            <div className="pmc-daily-report-meta">
                <p>
                    <span>{t('daily_reports.trigger')}</span>
                    <strong>{report.trigger_label}</strong>
                </p>
                <p>
                    <span>{t('daily_reports.initiated_by')}</span>
                    <strong>
                        {report.initiated_by ??
                            t('daily_reports.system_process')}
                    </strong>
                </p>
                <p>
                    <span>{t('daily_reports.finished_at')}</span>
                    <strong>
                        {finishedAt
                            ? dateTime(finishedAt, locale)
                            : t('daily_reports.pending')}
                    </strong>
                </p>
            </div>

            {report.failure_summary ? (
                <p className="pmc-daily-report-alert" role="alert">
                    <i
                        className="bi bi-exclamation-triangle"
                        aria-hidden="true"
                    />
                    {report.failure_summary}
                </p>
            ) : null}

            <footer>
                <Link className="btn btn-dark" href={report.show_url}>
                    <i className="bi bi-arrow-up-right" aria-hidden="true" />
                    {t('daily_reports.open_report')}
                </Link>
                {formats.map((format) =>
                    report.files[format].available ? (
                        <a
                            key={format}
                            className="btn btn-outline-secondary"
                            href={report.files[format].url}
                            download
                            title={bytes(report.files[format].bytes, locale)}
                        >
                            {format.toUpperCase()}
                        </a>
                    ) : null,
                )}
                {report.can_prune ? (
                    <button
                        type="button"
                        className="btn btn-outline-danger"
                        onClick={prune}
                    >
                        <i className="bi bi-trash3" aria-hidden="true" />
                        {t('daily_reports.prune')}
                    </button>
                ) : null}
            </footer>
        </article>
    );
}
