import type { InertiaFormProps } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { PresetVisibility, SavedReportFormData } from './types';

type SavedReportIdentitySectionProps = {
    form: InertiaFormProps<SavedReportFormData>;
    visibilityOptions: PresetVisibility[];
};

export function SavedReportIdentitySection({
    form,
    visibilityOptions,
}: SavedReportIdentitySectionProps) {
    const { t } = useTranslator();

    return (
        <section>
            <header>
                <span>01</span>
                <div>
                    <h2>{t('reports.report_identity')}</h2>
                    <p>{t('reports.report_identity_help')}</p>
                </div>
            </header>
            <div className="pmc-saved-report-form-grid">
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
                            form.setData(
                                'is_default',
                                event.currentTarget.checked,
                            )
                        }
                    />
                    <span>{t('reports.make_default')}</span>
                </label>
            </div>
        </section>
    );
}
