import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import { ReportPresetForm } from './report-preset-form';
import { ReportPresetList } from './report-preset-list';
import type {
    PresetVisibility,
    ReportFilterValues,
    ReportPreset,
} from './types';

export function ReportPresets({
    filters,
    presets,
    visibilityOptions,
}: {
    filters: ReportFilterValues;
    presets: ReportPreset[];
    visibilityOptions: PresetVisibility[];
}) {
    const { locale, t } = useTranslator();

    return (
        <details className="pmc-report-presets-compact">
            <summary>
                <div>
                    <i className="bi bi-bookmark" aria-hidden="true" />
                    <span>{t('reports.saved_views')}</span>
                    <strong>{localizedNumber(presets.length, locale)}</strong>
                </div>
                <i className="bi bi-chevron-down" aria-hidden="true" />
            </summary>
            <div className="pmc-report-presets-body">
                <ReportPresetForm
                    filters={filters}
                    visibilityOptions={visibilityOptions}
                />
                <ReportPresetList presets={presets} />
            </div>
        </details>
    );
}
