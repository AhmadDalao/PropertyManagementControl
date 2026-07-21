import { Link } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import {
    RecordActions,
    StatusBadge,
    humanLabel,
} from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { humanDate } from '@/lib/utils';

import { useDocumentFilterFields } from './document-filters';
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
    | 'app'
>;

export function DocumentTable(props: DocumentTableProps) {
    const { locale, t, text } = useTranslator();
    const filterFields = useDocumentFilterFields({
        types: props.typeOptions,
        attachments: props.attachmentOptions,
        visibilities: props.visibilityOptions,
        portfolios: props.portfolioOptions,
        includePortfolio:
            props.auth.user?.roles.includes('superadmin') ?? false,
    });

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
            columns={[
                {
                    key: 'title',
                    label: t('documents.document'),
                    render: (document) => (
                        <div className="pmc-primary-cell">
                            <strong>
                                {locale === 'ar'
                                    ? document.title_ar || document.title_en
                                    : document.title_en || document.title_ar}
                            </strong>
                            <span>{document.original_name}</span>
                            <StatusBadge
                                value={document.type}
                                label={text(humanLabel(document.type))}
                                tone="blue"
                            />
                        </div>
                    ),
                },
                {
                    key: 'attached',
                    label: t('documents.attached_to'),
                    render: (document) => (
                        <div className="pmc-stacked-cell">
                            {document.attachment.url ? (
                                <Link href={document.attachment.url}>
                                    <strong>{document.attachment.label}</strong>
                                </Link>
                            ) : (
                                <strong>{document.attachment.label}</strong>
                            )}
                            <span>
                                {text(humanLabel(document.attachment.type))} ·{' '}
                                {(locale === 'ar'
                                    ? document.portfolio.name_ar ||
                                      document.portfolio.name_en
                                    : document.portfolio.name_en ||
                                      document.portfolio.name_ar) ??
                                    t('documents.portfolio')}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'file',
                    label: t('documents.file'),
                    render: (document) => (
                        <div className="pmc-stacked-cell">
                            <strong>{formatBytes(document.file_size)}</strong>
                            <span>{t('documents.pdf')}</span>
                        </div>
                    ),
                },
                {
                    key: 'access',
                    label: t('documents.access'),
                    render: (document) => (
                        <div className="pmc-stacked-cell">
                            <StatusBadge
                                value={
                                    document.is_public ? 'public' : 'internal'
                                }
                                label={
                                    document.is_public
                                        ? t('documents.portal_visible')
                                        : t('documents.internal')
                                }
                                tone={
                                    document.is_public ? 'success' : 'neutral'
                                }
                            />
                            <span>
                                {t('documents.uploaded_by', undefined, {
                                    name:
                                        document.uploaded_by.name ??
                                        t('resource.system'),
                                })}{' '}
                                ·{' '}
                                {humanDate(
                                    document.created_at,
                                    props.app.locale,
                                )}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'actions',
                    label: t('documents.actions'),
                    className: 'text-end',
                    render: (document) => (
                        <RecordActions
                            showHref={`/documents/${document.id}`}
                            editHref={`/documents/${document.id}/edit`}
                        >
                            <a
                                href={document.download_url}
                                className="btn btn-outline-secondary btn-sm"
                            >
                                <i className="bi bi-file-earmark-pdf" />
                                <span>{t('documents.pdf')}</span>
                            </a>
                            <ArchiveAction
                                href={`/documents/${document.id}`}
                                label={t('actions.delete')}
                                confirmMessage={t(
                                    'documents.delete_confirm',
                                    undefined,
                                    {
                                        title:
                                            locale === 'ar'
                                                ? document.title_ar ||
                                                  document.title_en ||
                                                  ''
                                                : document.title_en ||
                                                  document.title_ar ||
                                                  '',
                                    },
                                )}
                            />
                        </RecordActions>
                    ),
                },
            ]}
        />
    );
}

function formatBytes(value: number): string {
    if (!value) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(
        Math.floor(Math.log(value) / Math.log(1024)),
        units.length - 1,
    );

    return `${(value / 1024 ** index).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
}
