import { useTranslator } from '@/lib/i18n';

import { CmsBuilderAttachForm } from './cms-builder-attach-form';
import { CmsBuilderLibraryList } from './cms-builder-library-list';
import type { CmsBuilderController } from './use-cms-builder';

export function CmsBuilderLibraryPane({
    builder,
}: {
    builder: CmsBuilderController;
}) {
    const { t } = useTranslator();

    return (
        <aside className="pmc-cms-library-pane">
            <header>
                <span>{t('cms.section_library')}</span>
                <h2>{t('cms.add_content')}</h2>
                <p>{t('cms.attach_help')}</p>
            </header>
            {builder.libraryLimitReached ? (
                <div className="alert alert-warning" role="status">
                    {t('cms.library_limit_notice')}
                </div>
            ) : null}
            <CmsBuilderAttachForm builder={builder} />
            <CmsBuilderLibraryList builder={builder} />
        </aside>
    );
}
