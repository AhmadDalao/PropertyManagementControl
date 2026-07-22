import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { CmsBuilderController } from './use-cms-builder';

export function CmsBuilderAttachForm({
    builder,
}: {
    builder: CmsBuilderController;
}) {
    const { t } = useTranslator();

    if (builder.sections.length === 0) {
        return (
            <div className="pmc-empty-state">
                <i className="bi bi-layout-text-window" />
                <strong>{t('cms.no_sections')}</strong>
                <Link href="/cms/sections/create" className="btn btn-primary">
                    {t('cms.create_new_section')}
                </Link>
            </div>
        );
    }

    return (
        <form onSubmit={builder.attachSection}>
            <label className="pmc-resource-field" htmlFor="cms-section-library">
                <span>{t('cms.sections')}</span>
                <select
                    id="cms-section-library"
                    className="form-select"
                    value={builder.attachForm.data.cms_section_id}
                    onChange={(event) =>
                        builder.attachForm.setData(
                            'cms_section_id',
                            event.currentTarget.value,
                        )
                    }
                >
                    {builder.sections.map((section) => (
                        <option key={section.id} value={section.id}>
                            {builder.localizedSectionName(section)} ·{' '}
                            {t(
                                `cms.section_types.${section.section_type}`,
                                section.section_type,
                            )}
                        </option>
                    ))}
                </select>
            </label>
            <label className="pmc-resource-check">
                <input
                    type="checkbox"
                    checked={builder.attachForm.data.is_visible}
                    onChange={(event) =>
                        builder.attachForm.setData(
                            'is_visible',
                            event.currentTarget.checked,
                        )
                    }
                />
                <span>
                    <strong>{t('cms.visible_after_attaching')}</strong>
                </span>
            </label>
            <button
                type="submit"
                className="btn btn-primary"
                disabled={builder.isBusy}
            >
                <i className="bi bi-plus-lg" />
                {t('cms.attach_section')}
            </button>
            <Link
                href="/cms/sections/create"
                className="btn btn-outline-secondary"
            >
                {t('cms.create_new_section')}
            </Link>
        </form>
    );
}
