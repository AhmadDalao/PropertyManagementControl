import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

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
            <CmsBuilderLibraryList builder={builder} />
            <Link
                href="/cms/sections/create"
                className="btn btn-outline-secondary"
            >
                <i className="bi bi-plus-lg" />
                {t('cms.create_new_section')}
            </Link>
        </aside>
    );
}
