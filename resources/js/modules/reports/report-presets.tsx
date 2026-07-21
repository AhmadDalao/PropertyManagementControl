import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { cleanReportFilters } from './report-query';
import type {
    PresetVisibility,
    ReportFilterValues,
    ReportPreset,
} from './types';

type Props = {
    filters: ReportFilterValues;
    presets: ReportPreset[];
    visibilityOptions: PresetVisibility[];
};

export function ReportPresets({ filters, presets, visibilityOptions }: Props) {
    const { locale, t } = useTranslator();
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
        <details className="pmc-report-presets-compact">
            <summary>
                <div>
                    <i className="bi bi-bookmark" aria-hidden="true" />
                    <span>{t('reports.saved_views')}</span>
                    <strong>{presets.length}</strong>
                </div>
                <i className="bi bi-chevron-down" aria-hidden="true" />
            </summary>
            <div className="pmc-report-presets-body">
                <form
                    className="pmc-report-preset-create"
                    onSubmit={savePreset}
                >
                    <label>
                        <span>{t('reports.preset_name_en')}</span>
                        <input
                            className="form-control"
                            value={form.data.title_en}
                            onChange={(event) =>
                                form.setData(
                                    'title_en',
                                    event.currentTarget.value,
                                )
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
                                form.setData(
                                    'title_ar',
                                    event.currentTarget.value,
                                )
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
                                    event.currentTarget
                                        .value as PresetVisibility,
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
                                form.setData(
                                    'is_default',
                                    event.currentTarget.checked,
                                )
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
                <div className="pmc-report-preset-list">
                    {presets.length > 0 ? (
                        presets.map((preset) => (
                            <article key={preset.id}>
                                <div>
                                    <strong>
                                        {locale === 'ar'
                                            ? preset.title_ar || preset.title_en
                                            : preset.title_en ||
                                              preset.title_ar}
                                    </strong>
                                    <span>
                                        {t(
                                            `reports.visibility_${preset.visibility}`,
                                        )}
                                        {preset.is_default
                                            ? ` · ${t('reports.default_view')}`
                                            : ''}
                                    </span>
                                </div>
                                <Link href={preset.url}>
                                    {t('actions.open')}
                                </Link>
                                {preset.can_delete ? (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.delete(
                                                `/reports/presets/${preset.id}`,
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        {t('reports.remove')}
                                    </button>
                                ) : null}
                            </article>
                        ))
                    ) : (
                        <p>{t('reports.no_saved_views')}</p>
                    )}
                </div>
            </div>
        </details>
    );
}
