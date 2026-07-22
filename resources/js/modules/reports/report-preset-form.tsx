import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { cleanReportFilters } from './report-query';
import type { PresetVisibility, ReportFilterValues } from './types';

export function ReportPresetForm({
    filters,
    visibilityOptions,
}: {
    filters: ReportFilterValues;
    visibilityOptions: PresetVisibility[];
}) {
    const { t } = useTranslator();
    const form = useForm({
        resource: 'portfolio-report',
        title_en: '',
        title_ar: '',
        visibility: visibilityOptions[0] ?? ('private' as PresetVisibility),
        is_default: false,
        filters_json: cleanReportFilters(filters),
    });

    const savePreset = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            filters_json: cleanReportFilters(filters),
        }));
        form.post('/reports/presets', {
            preserveScroll: true,
            onSuccess: () => form.reset('title_en', 'title_ar'),
        });
    };

    return (
        <form className="pmc-report-preset-create" onSubmit={savePreset}>
            <label>
                <span>{t('reports.preset_name_en')}</span>
                <input
                    className="form-control"
                    value={form.data.title_en}
                    onChange={(event) =>
                        form.setData('title_en', event.currentTarget.value)
                    }
                    required
                />
                {form.errors.title_en ? (
                    <small role="alert">{form.errors.title_en}</small>
                ) : null}
            </label>
            <label>
                <span>{t('reports.preset_name_ar')}</span>
                <input
                    className="form-control"
                    dir="rtl"
                    value={form.data.title_ar}
                    onChange={(event) =>
                        form.setData('title_ar', event.currentTarget.value)
                    }
                    required
                />
                {form.errors.title_ar ? (
                    <small role="alert">{form.errors.title_ar}</small>
                ) : null}
            </label>
            <label>
                <span>{t('reports.preset_visibility')}</span>
                <select
                    className="form-select"
                    value={form.data.visibility}
                    onChange={(event) =>
                        form.setData(
                            'visibility',
                            event.currentTarget.value as PresetVisibility,
                        )
                    }
                >
                    {visibilityOptions.map((visibility) => (
                        <option key={visibility} value={visibility}>
                            {t(`reports.visibility_${visibility}`)}
                        </option>
                    ))}
                </select>
            </label>
            <label className="pmc-report-default-check">
                <input
                    type="checkbox"
                    checked={form.data.is_default}
                    onChange={(event) =>
                        form.setData('is_default', event.currentTarget.checked)
                    }
                />
                <span>{t('reports.make_default')}</span>
            </label>
            <button
                type="submit"
                className="btn btn-primary"
                disabled={form.processing}
            >
                {form.processing
                    ? t('actions.working')
                    : t('reports.save_filters')}
            </button>
        </form>
    );
}
