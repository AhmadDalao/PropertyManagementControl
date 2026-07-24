import { Link } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { ReportFilterValues, ReportMode } from './types';

type Props = {
    filters: ReportFilterValues;
    filtersOpen: boolean;
    mode: ReportMode;
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: Array<{ id: number; portfolio_id: number; name: string }>;
    onChange: (filters: ReportFilterValues) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    onToggle: () => void;
};

export function ReportFilters({
    filters,
    filtersOpen,
    mode,
    portfolioOptions,
    propertyOptions,
    onChange,
    onSubmit,
    onToggle,
}: Props) {
    const { t } = useTranslator();
    const visibleProperties = propertyOptions.filter(
        (property) =>
            filters.portfolio_id === 'all' ||
            String(property.portfolio_id) === filters.portfolio_id,
    );

    return (
        <>
            <button
                type="button"
                className="pmc-report-filter-trigger"
                aria-expanded={filtersOpen}
                aria-controls="report-filter-panel"
                onClick={onToggle}
            >
                <i className="bi bi-sliders2" aria-hidden="true" />
                {filtersOpen
                    ? t('reports.hide_filters')
                    : t('reports.show_filters')}
            </button>

            <form
                id="report-filter-panel"
                className={`pmc-report-toolbar ${filtersOpen ? 'is-open' : ''}`}
                onSubmit={onSubmit}
            >
                <label>
                    <span>{t('reports.date_from')}</span>
                    <input
                        type="date"
                        className="form-control"
                        value={filters.date_from}
                        onChange={(event) =>
                            onChange({
                                ...filters,
                                date_from: event.currentTarget.value,
                            })
                        }
                    />
                </label>
                <label>
                    <span>{t('reports.date_to')}</span>
                    <input
                        type="date"
                        className="form-control"
                        value={filters.date_to}
                        onChange={(event) =>
                            onChange({
                                ...filters,
                                date_to: event.currentTarget.value,
                            })
                        }
                    />
                </label>
                {mode === 'superadmin' ? (
                    <label>
                        <span>{t('reports.portfolio')}</span>
                        <select
                            className="form-select"
                            value={filters.portfolio_id}
                            onChange={(event) => {
                                const portfolioId = event.currentTarget.value;
                                const propertyIsVisible = propertyOptions.some(
                                    (property) =>
                                        String(property.id) ===
                                            filters.property_id &&
                                        (portfolioId === 'all' ||
                                            String(property.portfolio_id) ===
                                                portfolioId),
                                );

                                onChange({
                                    ...filters,
                                    portfolio_id: portfolioId,
                                    property_id: propertyIsVisible
                                        ? filters.property_id
                                        : 'all',
                                });
                            }}
                        >
                            <option value="all">
                                {t('reports.all_portfolios')}
                            </option>
                            {portfolioOptions.map((portfolio) => (
                                <option key={portfolio.id} value={portfolio.id}>
                                    {portfolio.name}
                                </option>
                            ))}
                        </select>
                    </label>
                ) : null}
                <label>
                    <span>{t('reports.property')}</span>
                    <select
                        className="form-select"
                        value={filters.property_id}
                        onChange={(event) =>
                            onChange({
                                ...filters,
                                property_id: event.currentTarget.value,
                            })
                        }
                    >
                        <option value="all">
                            {t('reports.all_properties')}
                        </option>
                        {visibleProperties.map((property) => (
                            <option key={property.id} value={property.id}>
                                {property.name}
                            </option>
                        ))}
                    </select>
                </label>
                <div className="pmc-report-toolbar-actions">
                    <button className="btn btn-primary">
                        <i className="bi bi-funnel me-2" aria-hidden="true" />
                        {t('reports.apply')}
                    </button>
                    <Link href="/reports" className="btn btn-outline-secondary">
                        {t('actions.reset')}
                    </Link>
                </div>
            </form>
        </>
    );
}
