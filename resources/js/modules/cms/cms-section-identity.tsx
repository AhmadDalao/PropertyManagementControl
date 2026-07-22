import { useTranslator } from '@/lib/i18n';

import type { CmsSectionFormController } from './use-cms-section-form';

export function CmsSectionIdentity({
    controller,
    sectionTypes,
}: {
    controller: CmsSectionFormController;
    sectionTypes: Array<{ label: string; value: string }>;
}) {
    const { t } = useTranslator();
    const form = controller.form;

    return (
        <section className="pmc-workspace-panel">
            <div className="pmc-workspace-panel-head">
                <div>
                    <span>{t('cms.section_identity')}</span>
                    <h2>{t('cms.name_and_type')}</h2>
                    <p>{t('cms.identity_help')}</p>
                </div>
            </div>
            <div className="pmc-cms-section-basics">
                <label className="pmc-resource-field">
                    <span>{t('cms.section_type')}</span>
                    <select
                        className="form-select"
                        value={form.data.section_type}
                        onChange={(event) =>
                            form.setData(
                                'section_type',
                                event.currentTarget.value,
                            )
                        }
                    >
                        {sectionTypes.map((type) => (
                            <option key={type.value} value={type.value}>
                                {t(
                                    `cms.section_types.${type.value}`,
                                    type.label,
                                )}
                            </option>
                        ))}
                    </select>
                    {form.errors.section_type ? (
                        <em>{form.errors.section_type}</em>
                    ) : null}
                </label>
                <label className="pmc-resource-field">
                    <span>{t('cms.name_en')}</span>
                    <input
                        className="form-control"
                        value={form.data.name_en}
                        onChange={(event) =>
                            form.setData('name_en', event.currentTarget.value)
                        }
                        required
                    />
                    {form.errors.name_en ? (
                        <em>{form.errors.name_en}</em>
                    ) : null}
                </label>
                <label className="pmc-resource-field" dir="rtl">
                    <span>{t('cms.name_ar')}</span>
                    <input
                        className="form-control"
                        value={form.data.name_ar}
                        onChange={(event) =>
                            form.setData('name_ar', event.currentTarget.value)
                        }
                        required
                    />
                    {form.errors.name_ar ? (
                        <em>{form.errors.name_ar}</em>
                    ) : null}
                </label>
                <label className="pmc-resource-field">
                    <span>{t('cms.status')}</span>
                    <select
                        className="form-select"
                        value={form.data.status}
                        onChange={(event) =>
                            form.setData('status', event.currentTarget.value)
                        }
                    >
                        {['active', 'inactive', 'archived'].map((status) => (
                            <option key={status} value={status}>
                                {t(`status.${status}`)}
                            </option>
                        ))}
                    </select>
                </label>
            </div>
        </section>
    );
}
