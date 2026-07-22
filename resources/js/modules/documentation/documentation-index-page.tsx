import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/documentation.css';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { DocumentationCommand } from './documentation-command';
import { DocumentationControlChecks } from './documentation-control-checks';
import { DocumentationEmpty } from './documentation-empty';
import { DocumentationHeader } from './documentation-header';
import { DocumentationLibrary } from './documentation-library';
import { DocumentationWorkflows } from './documentation-workflows';
import type { DocumentationIndexPageProps } from './types';
import { useDocumentationSearch } from './use-documentation-search';

export default function DocumentationIndexPage() {
    const { props } = usePage<DocumentationIndexPageProps>();
    const { t } = useTranslator();
    const search = useDocumentationSearch(props);

    return (
        <AdminLayout>
            <Head title={t('docs.title')} />
            <div className="pmc-documentation-page">
                <DocumentationHeader />
                <DocumentationCommand
                    audience={props.audience}
                    roleGuide={props.roleGuide}
                    moduleStatus={props.moduleStatus}
                    quickStarts={props.quickStarts}
                    query={search.query}
                    onQueryChange={search.setQuery}
                />
                {search.hasResults ? (
                    <>
                        <DocumentationWorkflows workflows={search.workflows} />
                        <DocumentationLibrary
                            guides={search.guides}
                            shortcuts={search.shortcuts}
                        />
                        <DocumentationControlChecks
                            checks={search.checks}
                            searchActive={search.query.trim() !== ''}
                        />
                    </>
                ) : (
                    <DocumentationEmpty onClear={() => search.setQuery('')} />
                )}
            </div>
        </AdminLayout>
    );
}
