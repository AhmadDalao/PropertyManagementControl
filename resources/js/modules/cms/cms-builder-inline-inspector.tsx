import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { CmsPageSectionRecord } from './types';
import type { CmsBuilderController } from './use-cms-builder';
import { useCmsBuilderSectionEditor } from './use-cms-builder-section-editor';

export function CmsBuilderInlineInspector({
    builder,
    selected,
}: {
    builder: CmsBuilderController;
    selected: CmsPageSectionRecord;
}) {
    const { t } = useTranslator();
    const section = selected.section;
    const controller = useCmsBuilderSectionEditor(
        builder.page.id,
        section!,
        () => builder.setSaveState('saved'),
    );
    const submit = (event: FormEvent<HTMLFormElement>) => {
        builder.setSaveState('saving');
        controller.submit(event);
    };

    if (!section) {
        return (
            <div className="pmc-empty-state">{t('cms.missing_section')}</div>
        );
    }

    return (
        <form className="pmc-cms-inline-inspector" onSubmit={submit}>
            <div className="pmc-cms-inspector-tabs" role="tablist">
                <span className="active">EN</span>
                <span>AR</span>
                <span>{t('cms.instance', 'Instance')}</span>
            </div>
            <label className="pmc-resource-field">
                <span>{t('cms.name_en')}</span>
                <input
                    className="form-control"
                    value={controller.form.data.name_en}
                    required
                    onChange={(event) =>
                        controller.form.setData(
                            'name_en',
                            event.currentTarget.value,
                        )
                    }
                />
            </label>
            <label className="pmc-resource-field" dir="rtl">
                <span>{t('cms.name_ar')}</span>
                <input
                    className="form-control"
                    value={controller.form.data.name_ar}
                    required
                    onChange={(event) =>
                        controller.form.setData(
                            'name_ar',
                            event.currentTarget.value,
                        )
                    }
                />
            </label>
            <label className="pmc-resource-field">
                <span>{t('cms.status')}</span>
                <select
                    className="form-select"
                    value={controller.form.data.status}
                    onChange={(event) =>
                        controller.form.setData(
                            'status',
                            event.currentTarget.value,
                        )
                    }
                >
                    {['active', 'inactive', 'archived'].map((status) => (
                        <option key={status} value={status}>
                            {t(`status.${status}`)}
                        </option>
                    ))}
                </select>
            </label>
            <label className="pmc-cms-visibility-control">
                <span>{t('cms.visible_on_page', 'Visible on page')}</span>
                <input
                    type="checkbox"
                    checked={selected.is_visible}
                    disabled={builder.isBusy}
                    onChange={() => builder.toggleVisibility(selected)}
                />
            </label>
            {(section.page_sections_count ?? 0) > 1 ? (
                <div className="pmc-cms-shared-warning">
                    <i className="bi bi-diagram-3" />
                    {t('cms.shared_warning', undefined, {
                        count: section.page_sections_count ?? 0,
                    })}
                </div>
            ) : null}
            <button
                type="button"
                className="btn btn-outline-secondary"
                onClick={() => builder.openEditor(selected)}
            >
                <i className="bi bi-pencil" />
                {t('cms.edit_bilingual_content')}
            </button>
            <button
                type="submit"
                className="btn btn-primary"
                disabled={controller.form.processing}
            >
                {controller.form.processing
                    ? t('cms.saving')
                    : t('actions.save')}
            </button>
            <div className="pmc-cms-inspector-order">
                <button
                    type="button"
                    className="btn btn-light"
                    disabled={
                        builder.isBusy ||
                        builder.orderedSections[0]?.id === selected.id
                    }
                    onClick={() => builder.moveSection(selected.id, -1)}
                >
                    <i className="bi bi-arrow-up" />
                    {t('cms.move_up')}
                </button>
                <button
                    type="button"
                    className="btn btn-light"
                    disabled={
                        builder.isBusy ||
                        builder.orderedSections.at(-1)?.id === selected.id
                    }
                    onClick={() => builder.moveSection(selected.id, 1)}
                >
                    <i className="bi bi-arrow-down" />
                    {t('cms.move_down')}
                </button>
            </div>
        </form>
    );
}
