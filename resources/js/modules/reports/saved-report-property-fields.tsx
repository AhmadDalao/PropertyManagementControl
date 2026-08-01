import type { Dispatch, SetStateAction } from 'react';

import { useTranslator } from '@/lib/i18n';
import { PropertySelectionField } from '@/modules/shell/property-selection-field';
import type { SharedProps } from '@/types';

import type {
    PresetVisibility,
    ReportFilterValues,
    SavedReportFormPageProps,
} from './types';

type SavedReportPropertyFieldsProps = {
    auth: SharedProps['auth'];
    filters: ReportFilterValues;
    portfolioOptions: SavedReportFormPageProps['portfolioOptions'];
    propertyContext: SavedReportFormPageProps['propertyContext'];
    propertyOptions: SavedReportFormPageProps['propertyOptions'];
    setFilters: Dispatch<SetStateAction<ReportFilterValues>>;
    visibility: PresetVisibility;
};

export function SavedReportPropertyFields({
    auth,
    filters,
    portfolioOptions,
    propertyContext,
    propertyOptions,
    setFilters,
    visibility,
}: SavedReportPropertyFieldsProps) {
    const { t } = useTranslator();

    if (visibility === 'global') {
        return (
            <div className="pmc-report-period-note">
                <i className="bi bi-globe2" aria-hidden="true" />
                <span>{t('reports.global_scope_help')}</span>
            </div>
        );
    }

    const visibleProperties = propertyOptions.filter(
        (property) =>
            filters.portfolio_id === 'all' ||
            String(property.portfolio_id) === filters.portfolio_id,
    );
    const visiblePropertyIds = new Set(
        visibleProperties.map((property) => property.id),
    );
    const visiblePropertyContext = propertyContext
        ? {
              ...propertyContext,
              options: propertyContext.options.filter((property) =>
                  visiblePropertyIds.has(property.id),
              ),
          }
        : null;

    return (
        <>
            {auth.user?.roles.includes('superadmin') ? (
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

                            setFilters((current) => ({
                                ...current,
                                portfolio_id: portfolioId,
                                property_id: propertyIsVisible
                                    ? current.property_id
                                    : 'all',
                            }));
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
            {visiblePropertyContext ? (
                <PropertySelectionField
                    label={t('reports.property')}
                    context={visiblePropertyContext}
                    value={
                        filters.property_id === 'all' ? '' : filters.property_id
                    }
                    testId="saved-report-property-filter"
                    onChange={(propertyId) =>
                        setFilters((current) => ({
                            ...current,
                            property_id: propertyId || 'all',
                        }))
                    }
                />
            ) : (
                <label>
                    <span>{t('reports.property')}</span>
                    <select
                        className="form-select"
                        value={filters.property_id}
                        onChange={(event) =>
                            setFilters((current) => ({
                                ...current,
                                property_id: event.currentTarget.value,
                            }))
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
            )}
        </>
    );
}
