import type { Dispatch, SetStateAction } from 'react';

import { useTranslator } from '@/lib/i18n';
import type { SharedProps } from '@/types';

import { SavedReportPeriodFields } from './saved-report-period-fields';
import { SavedReportPropertyFields } from './saved-report-property-fields';
import type {
    PresetVisibility,
    ReportFilterValues,
    SavedReportFormPageProps,
} from './types';

type SavedReportScopeSectionProps = {
    auth: SharedProps['auth'];
    filters: ReportFilterValues;
    portfolioOptions: SavedReportFormPageProps['portfolioOptions'];
    propertyContext: SavedReportFormPageProps['propertyContext'];
    propertyOptions: SavedReportFormPageProps['propertyOptions'];
    setFilters: Dispatch<SetStateAction<ReportFilterValues>>;
    visibility: PresetVisibility;
};

export function SavedReportScopeSection({
    auth,
    filters,
    portfolioOptions,
    propertyContext,
    propertyOptions,
    setFilters,
    visibility,
}: SavedReportScopeSectionProps) {
    const { t } = useTranslator();

    return (
        <section>
            <header>
                <span>02</span>
                <div>
                    <h2>{t('reports.report_scope_period')}</h2>
                    <p>{t('reports.report_scope_period_help')}</p>
                </div>
            </header>
            <div className="pmc-saved-report-form-grid">
                <SavedReportPeriodFields
                    filters={filters}
                    setFilters={setFilters}
                />
                <SavedReportPropertyFields
                    auth={auth}
                    filters={filters}
                    portfolioOptions={portfolioOptions}
                    propertyContext={propertyContext}
                    propertyOptions={propertyOptions}
                    setFilters={setFilters}
                    visibility={visibility}
                />
            </div>
        </section>
    );
}
