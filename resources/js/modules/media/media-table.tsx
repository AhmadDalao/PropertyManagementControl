import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { mediaFilterFields } from './media-filters';
import {
    formatMediaBytes,
    formatMediaDimensions,
    localizedMediaAlt,
    localizedMediaTitle,
} from './media-format';
import type { MediaIndexPageProps, MediaRecord } from './types';

export function MediaTable({ props }: { props: MediaIndexPageProps }) {
    const { locale, t } = useTranslator();
    const title = (media: MediaRecord) =>
        localizedMediaTitle(media, locale) || t('media.untitled');

    return (
        <DataTable
            title={t('media.register_title')}
            description={t('media.register_description')}
            data={props.mediaFiles}
            filters={props.filters}
            counts={props.counts}
            basePath="/media-files"
            rowHref={(media) => `/media-files/${media.id}`}
            exportHref={exportUrl('/exports/media-files', props.filters)}
            filterFields={mediaFilterFields(props, t)}
            emptyText={t('media.no_matches')}
            createHref="/media-files/create"
            createLabel={t('media.upload_media')}
            mobileCard={{
                title: (media) => <MediaIdentity media={media} />,
                subtitle: (media) => media.filename,
                status: (media) => <StatusBadge value={media.visibility} />,
                meta: [
                    {
                        label: t('media.collection'),
                        value: (media) => media.collection,
                    },
                    {
                        label: t('media.dimensions'),
                        value: formatMediaDimensions,
                    },
                    {
                        label: t('media.size'),
                        value: (media) => formatMediaBytes(media.size, locale),
                    },
                ],
                actions: (media) => (
                    <MediaActions media={media} title={title(media)} />
                ),
            }}
            columns={[
                {
                    key: 'title',
                    label: t('media.media_column'),
                    render: (media) => <MediaIdentity media={media} />,
                },
                {
                    key: 'file',
                    label: t('media.file_column'),
                    render: (media) => (
                        <div className="pmc-stacked-cell">
                            <strong>{media.filename}</strong>
                            <span>
                                {media.mime_type ?? t('media.unknown_type')} ·{' '}
                                {formatMediaBytes(media.size, locale)}
                            </span>
                        </div>
                    ),
                },
                {
                    key: 'dimensions',
                    label: t('media.dimensions_column'),
                    render: formatMediaDimensions,
                },
                {
                    key: 'collection',
                    label: t('media.collection'),
                    render: (media) => (
                        <StatusBadge value={media.collection} tone="blue" />
                    ),
                },
                {
                    key: 'visibility',
                    label: t('media.visibility_column'),
                    render: (media) => <StatusBadge value={media.visibility} />,
                },
                {
                    key: 'actions',
                    label: t('media.actions_column'),
                    className: 'text-end',
                    render: (media) => (
                        <MediaActions media={media} title={title(media)} />
                    ),
                },
            ]}
        />
    );
}

function MediaIdentity({ media }: { media: MediaRecord }) {
    const { locale, t } = useTranslator();

    return (
        <div className="pmc-media-primary-cell">
            <img
                className="pmc-media-thumb"
                src={media.file_url}
                alt={localizedMediaAlt(media, locale)}
                loading="lazy"
            />
            <div className="pmc-primary-cell">
                <strong>
                    {localizedMediaTitle(media, locale) || t('media.untitled')}
                </strong>
                <span>{media.uploaded_by?.name || media.collection}</span>
            </div>
        </div>
    );
}

function MediaActions({ media, title }: { media: MediaRecord; title: string }) {
    const { t } = useTranslator();

    return (
        <RecordActions
            showHref={`/media-files/${media.id}`}
            editHref={`/media-files/${media.id}/edit`}
        >
            <a
                className="btn btn-outline-secondary btn-sm"
                href={media.file_url}
                target="_blank"
                rel="noreferrer"
            >
                <i className="bi bi-box-arrow-up-right" />
                <span>{t('media.open_file')}</span>
            </a>
            <ArchiveAction
                href={`/media-files/${media.id}`}
                label={t('actions.delete')}
                confirmMessage={t('media.delete_confirm', undefined, { title })}
            />
        </RecordActions>
    );
}
