import { useTranslator } from '@/lib/i18n';

import { CmsBuilderHistory } from './cms-builder-history';
import { CmsBuilderOutline } from './cms-builder-outline';
import { CmsBuilderSelection } from './cms-builder-selection';
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
                <span>{t('cms.page_outline')}</span>
                <h2>{t('cms.sections')}</h2>
                <p>{t('cms.reorder_help')}</p>
            </header>
            <CmsBuilderOutline builder={builder} />
            <CmsBuilderSelection builder={builder} />
            <CmsBuilderHistory timeline={builder.timeline} />
        </aside>
    );
}
