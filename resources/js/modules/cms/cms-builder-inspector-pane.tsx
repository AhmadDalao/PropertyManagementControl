import { useTranslator } from '@/lib/i18n';

import { CmsBuilderHistory } from './cms-builder-history';
import { CmsBuilderInlineInspector } from './cms-builder-inline-inspector';
import type { CmsBuilderController } from './use-cms-builder';

export function CmsBuilderInspectorPane({
    builder,
}: {
    builder: CmsBuilderController;
}) {
    const { t } = useTranslator();

    return (
        <aside className="pmc-cms-inspector-pane">
            <header>
                <span>{t('cms.selected_section')}</span>
                <h2>{t('cms.inspector', 'Inspector')}</h2>
                <p>
                    {t(
                        'cms.inspector_help',
                        'Edit the selected section and its page settings.',
                    )}
                </p>
            </header>
            {builder.selected ? (
                <CmsBuilderInlineInspector
                    key={builder.selected.id}
                    builder={builder}
                    selected={builder.selected}
                />
            ) : (
                <div className="pmc-empty-state">
                    {t('cms.select_section_help')}
                </div>
            )}
            <CmsBuilderHistory timeline={builder.timeline} />
        </aside>
    );
}
