import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { useDocumentFilterFields } from './document-filters';
import { useDocumentTableConfig } from './document-table-config';
import type { DocumentIndexPageProps } from './types';

type DocumentTableProps = Pick<
    DocumentIndexPageProps,
    | 'documents'
    | 'filters'
    | 'counts'
    | 'portfolioOptions'
    | 'typeOptions'
    | 'attachmentOptions'
    | 'visibilityOptions'
    | 'auth'
>;

export function DocumentTable(props: DocumentTableProps) {
    const { locale, t } = useTranslator();
    const filterFields = useDocumentFilterFields({
        types: props.typeOptions,
        attachments: props.attachmentOptions,
        visibilities: props.visibilityOptions,
        portfolios: props.portfolioOptions,
        includePortfolio:
            props.auth.user?.roles.includes('superadmin') ?? false,
    });
    const table = useDocumentTableConfig(locale);

    return (
        <DataTable
            title={t('documents.register_title')}
            description={t('documents.register_description')}
            data={props.documents}
            filters={props.filters}
            counts={props.counts}
            basePath="/documents"
            rowHref={(document) => `/documents/${document.id}`}
            exportHref={exportUrl('/exports/documents', props.filters)}
            filterFields={filterFields}
            emptyText={t('documents.empty')}
            columns={table.columns}
            mobileCard={table.mobileCard}
        />
    );
}
