import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import type { DailyReportRecord } from './types';

export function ReportDetailPanels({ report }: { report: DailyReportRecord }) {
    const { locale, t } = useTranslator();
    const formats = ['pdf', 'docx', 'xlsx'] as const;

    return (
        <div className="pmc-daily-report-detail-grid">
            <section className="pmc-daily-report-panel">
                <header>
                    <i className="bi bi-diagram-3" aria-hidden="true" />
                    <h2>{t('daily_reports.work_by_type')}</h2>
                </header>
                <div className="pmc-daily-report-position-grid">
                    {(report.summary.types ?? []).map((position) => (
                        <div key={position.type}>
                            <span>
                                {t(`action_center.type_${position.type}`)}
                            </span>
                            <strong>
                                {position.count.toLocaleString(locale)}
                            </strong>
                        </div>
                    ))}
                </div>
            </section>

            <section className="pmc-daily-report-panel">
                <header>
                    <i className="bi bi-cash-stack" aria-hidden="true" />
                    <h2>{t('daily_reports.financial_exposure')}</h2>
                </header>
                {(report.summary.currencies ?? []).length > 0 ? (
                    <div className="pmc-daily-report-position-grid">
                        {(report.summary.currencies ?? []).map((position) => (
                            <div key={position.currency}>
                                <span>
                                    {position.count.toLocaleString(locale)}{' '}
                                    {t('daily_reports.actions')}
                                </span>
                                <strong>
                                    {currency(
                                        position.amount,
                                        locale,
                                        position.currency,
                                    )}
                                </strong>
                            </div>
                        ))}
                    </div>
                ) : (
                    <p>{t('daily_reports.no_financial_exposure')}</p>
                )}
            </section>

            <section className="pmc-daily-report-panel">
                <header>
                    <i className="bi bi-funnel" aria-hidden="true" />
                    <h2>{t('daily_reports.applied_scope')}</h2>
                </header>
                <dl className="pmc-daily-report-scope">
                    {report.scope.map((item) => (
                        <div key={`${item.label}-${item.value}`}>
                            <dt>{item.label}</dt>
                            <dd>{item.value}</dd>
                        </div>
                    ))}
                </dl>
            </section>

            <section className="pmc-daily-report-panel">
                <header>
                    <i className="bi bi-file-earmark-lock" aria-hidden="true" />
                    <h2>{t('daily_reports.protected_files')}</h2>
                </header>
                <p>{t('daily_reports.protected_files_help')}</p>
                <div className="pmc-daily-report-downloads">
                    {formats.map((format) => (
                        <a
                            key={format}
                            href={report.files[format].url}
                            className={`btn ${
                                report.files[format].available
                                    ? 'btn-dark'
                                    : 'btn-outline-secondary disabled'
                            }`}
                            aria-disabled={!report.files[format].available}
                            download
                        >
                            <i className="bi bi-download" aria-hidden="true" />
                            {t('daily_reports.download_format', undefined, {
                                format: format.toUpperCase(),
                            })}
                        </a>
                    ))}
                </div>
            </section>
        </div>
    );
}
