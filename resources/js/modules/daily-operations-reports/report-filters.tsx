import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { DailyReportIndexProps } from './types';

export function ReportFilters({ props }: { props: DailyReportIndexProps }) {
    const { t } = useTranslator();
    const [filters, setFilters] = useState({
        status: props.filters.status,
        portfolio_id: props.filters.portfolio_id
            ? String(props.filters.portfolio_id)
            : 'all',
        date_from: props.filters.date_from,
        date_to: props.filters.date_to,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(
            '/reports/daily-operations',
            {
                status: filters.status === 'all' ? undefined : filters.status,
                portfolio_id:
                    filters.portfolio_id === 'all'
                        ? undefined
                        : filters.portfolio_id,
                date_from: filters.date_from || undefined,
                date_to: filters.date_to || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <form className="pmc-daily-report-filters" onSubmit={submit}>
            <label>
                <span>{t('daily_reports.filter_status')}</span>
                <select
                    value={filters.status}
                    onChange={(event) =>
                        setFilters({ ...filters, status: event.target.value })
                    }
                >
                    {props.statusOptions.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            </label>
            {props.canSelectGlobal ? (
                <label>
                    <span>{t('daily_reports.filter_portfolio')}</span>
                    <select
                        value={filters.portfolio_id}
                        onChange={(event) =>
                            setFilters({
                                ...filters,
                                portfolio_id: event.target.value,
                            })
                        }
                    >
                        <option value="all">
                            {t('daily_reports.global_scope')}
                        </option>
                        {props.portfolioOptions.map((portfolio) => (
                            <option key={portfolio.id} value={portfolio.id}>
                                {portfolio.name}
                            </option>
                        ))}
                    </select>
                </label>
            ) : null}
            <label>
                <span>{t('daily_reports.date_from')}</span>
                <input
                    type="date"
                    value={filters.date_from}
                    onChange={(event) =>
                        setFilters({
                            ...filters,
                            date_from: event.target.value,
                        })
                    }
                />
            </label>
            <label>
                <span>{t('daily_reports.date_to')}</span>
                <input
                    type="date"
                    value={filters.date_to}
                    onChange={(event) =>
                        setFilters({ ...filters, date_to: event.target.value })
                    }
                />
            </label>
            <div className="pmc-daily-report-filter-actions">
                <button type="submit" className="btn btn-warning">
                    {t('daily_reports.apply_filters')}
                </button>
                <button
                    type="button"
                    className="btn btn-outline-secondary"
                    onClick={() => router.get('/reports/daily-operations')}
                >
                    {t('daily_reports.reset_filters')}
                </button>
            </div>
        </form>
    );
}
