import { Link } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import {
    RecordActions,
    StatusBadge,
    humanLabel,
} from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import { humanDate } from '@/lib/utils';

import type { DocumentRecord } from './types';

export function useDocumentTableConfig(locale: string): {
    columns: Array<TableColumn<DocumentRecord>>;
    mobileCard: MobileTableConfig<DocumentRecord>;
} {
    const { t } = useTranslator();
    const option = (value: string) =>
        t(`documents.options.${value}` as UiTranslationKey, humanLabel(value));
    const title = (document: DocumentRecord) => (
        <div className="pmc-primary-cell">
            <strong>
                {locale === 'ar'
                    ? document.title_ar || document.title_en
                    : document.title_en || document.title_ar}
            </strong>
            <span>{document.original_name}</span>
        </div>
    );
    const desktopTitle = (document: DocumentRecord) => (
        <div className="pmc-primary-cell">
            <strong>
                {locale === 'ar'
                    ? document.title_ar || document.title_en
                    : document.title_en || document.title_ar}
            </strong>
            <span>{document.original_name}</span>
            <StatusBadge
                value={document.type}
                label={option(document.type)}
                tone="blue"
            />
        </div>
    );
    const attachment = (document: DocumentRecord) => (
        <div className="pmc-stacked-cell">
            {document.attachment.url ? (
                <Link href={document.attachment.url}>
                    <strong>{document.attachment.label}</strong>
                </Link>
            ) : (
                <strong>{document.attachment.label}</strong>
            )}
            <span>
                {option(document.attachment.type)} ·{' '}
                {(locale === 'ar'
                    ? document.portfolio.name_ar || document.portfolio.name_en
                    : document.portfolio.name_en ||
                      document.portfolio.name_ar) ?? t('documents.portfolio')}
            </span>
        </div>
    );
    const file = (document: DocumentRecord) => (
        <div className="pmc-stacked-cell">
            <strong>{formatBytes(document.file_size)}</strong>
            <span>{t('documents.pdf')}</span>
        </div>
    );
    const access = (document: DocumentRecord) => (
        <StatusBadge
            value={document.is_public ? 'public' : 'internal'}
            label={
                document.is_public
                    ? t('documents.portal_visible')
                    : t('documents.internal')
            }
            tone={document.is_public ? 'success' : 'neutral'}
        />
    );
    const uploader = (document: DocumentRecord) => (
        <div className="pmc-stacked-cell">
            {access(document)}
            <span>
                {t('documents.uploaded_by', undefined, {
                    name: document.uploaded_by.name ?? t('resource.system'),
                })}{' '}
                · {humanDate(document.created_at, locale)}
            </span>
        </div>
    );
    const actions = (document: DocumentRecord) => (
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
                confirmMessage={t('documents.delete_confirm', undefined, {
                    title:
                        locale === 'ar'
                            ? document.title_ar || document.title_en || ''
                            : document.title_en || document.title_ar || '',
                })}
            />
        </RecordActions>
    );

    return {
        columns: [
            {
                key: 'title',
                label: t('documents.document'),
                render: desktopTitle,
            },
            {
                key: 'attached',
                label: t('documents.attached_to'),
                render: attachment,
            },
            { key: 'file', label: t('documents.file'), render: file },
            {
                key: 'access',
                label: t('documents.access'),
                render: uploader,
            },
            {
                key: 'actions',
                label: t('documents.actions'),
                className: 'text-end',
                render: actions,
            },
        ],
        mobileCard: {
            title,
            subtitle: (document) => (
                <StatusBadge
                    value={document.type}
                    label={option(document.type)}
                    tone="blue"
                />
            ),
            status: access,
            meta: [
                { label: t('documents.attached_to'), value: attachment },
                { label: t('documents.file'), value: file },
                {
                    label: t('documents.uploader'),
                    value: (document) =>
                        document.uploaded_by.name ?? t('resource.system'),
                },
            ],
            actions,
        },
    };
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
