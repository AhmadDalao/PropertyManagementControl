import { useTranslator } from '@/lib/i18n';

import type { CmsBuilderController } from './use-cms-builder';

export function CmsBuilderLibraryList({
    builder,
}: {
    builder: CmsBuilderController;
}) {
    const { locale, t } = useTranslator();

    return (
        <div className="pmc-cms-library-list">
            {builder.sections.slice(0, 12).map((section) => (
                <article key={section.id}>
                    <i className="bi bi-layout-text-window" />
                    <div>
                        <span>
                            {t(
                                `cms.section_types.${section.section_type}`,
                                section.section_type,
                            )}
                        </span>
                        <strong>{builder.localizedSectionName(section)}</strong>
                        <small>
                            {locale === 'ar'
                                ? section.name_en
                                : section.name_ar}
                        </small>
                    </div>
                    <button
                        type="button"
                        aria-label={t(
                            'cms.attach_named_section',
                            'Attach :name',
                            {
                                name: builder.localizedSectionName(section),
                            },
                        )}
                        disabled={builder.isBusy}
                        onClick={() => builder.attachSectionById(section.id)}
                    >
                        <i className="bi bi-plus-lg" aria-hidden="true" />
                    </button>
                </article>
            ))}
        </div>
    );
}
