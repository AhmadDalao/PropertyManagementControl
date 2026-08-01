import type { Dispatch, SetStateAction } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { ReportFilterValues, ReportPeriod } from './types';

type SavedReportPeriodFieldsProps = {
    filters: ReportFilterValues;
    setFilters: Dispatch<SetStateAction<ReportFilterValues>>;
};

const periods: ReportPeriod[] = [
    'custom',
    'this_month',
    'last_month',
    'last_30_days',
    'year_to_date',
];

export function SavedReportPeriodFields({
    filters,
    setFilters,
}: SavedReportPeriodFieldsProps) {
    const { t } = useTranslator();

    return (
        <>
            <label>
                <span>{t('reports.period')}</span>
                <select
                    className="form-select"
                    value={filters.period}
                    onChange={(event) =>
                        setFilters((current) => ({
                            ...current,
                            period: event.currentTarget.value as ReportPeriod,
                        }))
                    }
                >
                    {periods.map((period) => (
                        <option key={period} value={period}>
                            {t(`reports.period_${period}`)}
                        </option>
                    ))}
                </select>
            </label>
            {filters.period === 'custom' ? (
                <>
                    <label>
                        <span>{t('reports.date_from')}</span>
                        <input
                            type="date"
                            className="form-control"
                            value={filters.date_from}
                            onChange={(event) =>
                                setFilters((current) => ({
                                    ...current,
                                    date_from: event.currentTarget.value,
                                }))
                            }
                            required
                        />
                    </label>
                    <label>
                        <span>{t('reports.date_to')}</span>
                        <input
                            type="date"
                            className="form-control"
                            value={filters.date_to}
                            onChange={(event) =>
                                setFilters((current) => ({
                                    ...current,
                                    date_to: event.currentTarget.value,
                                }))
                            }
                            required
                        />
                    </label>
                </>
            ) : (
                <div className="pmc-report-period-note">
                    <i className="bi bi-arrow-repeat" aria-hidden="true" />
                    <span>{t('reports.rolling_period_help')}</span>
                </div>
            )}
        </>
    );
}
