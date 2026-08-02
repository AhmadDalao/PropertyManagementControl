import { TablePagination } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { ReportCard } from './report-card';
import { ReportFilters } from './report-filters';
import type { DailyReportIndexProps } from './types';

export function ReportHistory({ props }: { props: DailyReportIndexProps }) {
    const { t } = useTranslator();

    return (
        <section className="pmc-daily-report-history">
            <header>
                <div>
                    <span>{t('daily_reports.history_eyebrow')}</span>
                    <h2>{t('daily_reports.history_title')}</h2>
                    <p>{t('daily_reports.history_description')}</p>
                </div>
            </header>
            <ReportFilters props={props} />

            {props.reports.data.length > 0 ? (
                <>
                    <div className="pmc-daily-report-grid">
                        {props.reports.data.map((report) => (
                            <ReportCard key={report.id} report={report} />
                        ))}
                    </div>
                    <TablePagination data={props.reports} />
                </>
            ) : (
                <div className="pmc-daily-report-empty">
                    <i className="bi bi-archive" aria-hidden="true" />
                    <h3>{t('daily_reports.empty_title')}</h3>
                    <p>{t('daily_reports.empty_description')}</p>
                </div>
            )}

            <p className="pmc-daily-report-retention">
                {t('daily_reports.retention_help', undefined, {
                    days: props.summary.retention_days,
                })}
            </p>
        </section>
    );
}
