import { ArchiveAction } from '@/components/archive-action';
import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import {
    formatMediaBytes,
    formatMediaDimensions,
    localizedMediaAlt,
    localizedMediaTitle,
} from './media-format';
import type { MediaRecord } from './types';

export function useMediaTableConfig(locale: 'en' | 'ar'): {
    columns: Array<TableColumn<MediaRecord>>;
    mobileCard: MobileTableConfig<MediaRecord>;
} {
    const { t } = useTranslator();
    const titleText = (media: MediaRecord) =>
        localizedMediaTitle(media, locale) || t('media.untitled');
    const identity = (media: MediaRecord) => (
        <div className="pmc-media-primary-cell">
            <img
                className="pmc-media-thumb"
                src={media.file_url}
                alt={localizedMediaAlt(media, locale)}
                loading="lazy"
            />
            <div className="pmc-primary-cell">
                <strong>{titleText(media)}</strong>
                <span>{media.uploaded_by?.name || media.filename}</span>
            </div>
        </div>
    );
    const file = (media: MediaRecord) => (
        <div className="pmc-stacked-cell">
            <strong>{media.filename}</strong>
            <span>
                {media.mime_type ?? t('media.unknown_type')} ·{' '}
                {formatMediaBytes(media.size, locale)}
            </span>
        </div>
    );
    const scope = (media: MediaRecord) => (
        <div className="pmc-stacked-cell">
            <StatusBadge value={media.collection} tone="blue" />
            <span>
                {localizedPortfolio(media, locale, t('media.global_website'))}
            </span>
        </div>
    );
    const access = (media: MediaRecord) => (
        <StatusBadge
            value={media.visibility}
            label={
                media.visibility === 'public'
                    ? t('media.public')
                    : t('media.private')
            }
        />
    );
    const actions = (media: MediaRecord) => (
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
                confirmMessage={t('media.delete_confirm', undefined, {
                    title: titleText(media),
                })}
            />
        </RecordActions>
    );

    return {
        columns: [
            { key: 'title', label: t('media.media_column'), render: identity },
            { key: 'scope', label: t('media.scope_column'), render: scope },
            { key: 'file', label: t('media.file_column'), render: file },
            {
                key: 'dimensions',
                label: t('media.dimensions_column'),
                render: formatMediaDimensions,
            },
            {
                key: 'visibility',
                label: t('media.visibility_column'),
                render: access,
            },
            {
                key: 'actions',
                label: t('media.actions_column'),
                className: 'text-end',
                render: actions,
            },
        ],
        mobileCard: {
            title: identity,
            subtitle: (media) => media.filename,
            status: access,
            meta: [
                { label: t('media.scope_column'), value: scope },
                {
                    label: t('media.dimensions'),
                    value: formatMediaDimensions,
                },
                {
                    label: t('media.size'),
                    value: (media) => formatMediaBytes(media.size, locale),
                },
            ],
            actions,
        },
    };
}

function localizedPortfolio(
    media: MediaRecord,
    locale: 'en' | 'ar',
    fallback: string,
): string {
    const portfolio = media.portfolio;

    if (!portfolio) {
        return fallback;
    }

    return locale === 'ar'
        ? portfolio.name_ar || portfolio.name_en || fallback
        : portfolio.name_en || portfolio.name_ar || fallback;
}
