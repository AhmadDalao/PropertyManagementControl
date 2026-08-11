import { Link } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { exportUrl } from '@/components/data-table';
import { TableHeader } from '@/components/data-table/table-header';
import { TablePagination } from '@/components/data-table/table-pagination';
import { TableToolbar } from '@/components/data-table/table-toolbar';
import { useTableQuery } from '@/components/data-table/use-table-query';
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

export function MediaLibrary({ props }: { props: MediaIndexPageProps }) {
    const { locale, t } = useTranslator();
    const query = useTableQuery({
        filters: props.filters,
        basePath: '/media-files',
    });

    return (
        <section className="pmc-operations-table pmc-media-library">
            <TableHeader
                title={t('media.register_title')}
                description={t('media.register_description')}
                total={props.mediaFiles.total}
                exportHref={exportUrl('/exports/media-files', props.filters)}
            />
            <TableToolbar
                counts={props.counts}
                filterFields={mediaFilterFields(props, t)}
                draftFilters={query.draftFilters}
                activeFilters={query.activeFilters}
                filtersOpen={query.filtersOpen}
                isSearching={query.isSearching}
                setDraftFilters={query.setDraftFilters}
                setFiltersOpen={query.setFiltersOpen}
                visit={query.visit}
                reset={query.reset}
                removeFilter={query.removeFilter}
            />
            {props.mediaFiles.data.length > 0 ? (
                <div className="pmc-media-library-grid">
                    {props.mediaFiles.data.map((media) => (
                        <MediaCard
                            media={media}
                            locale={locale}
                            key={media.id}
                        />
                    ))}
                </div>
            ) : (
                <div className="pmc-media-library-empty">
                    <i className="bi bi-images" />
                    <strong>{t('media.no_matches')}</strong>
                    <Link href="/media-files/create">
                        {t('media.upload_media')}
                    </Link>
                </div>
            )}
            <TablePagination data={props.mediaFiles} />
        </section>
    );
}

function MediaCard({
    media,
    locale,
}: {
    media: MediaRecord;
    locale: 'en' | 'ar';
}) {
    const { t } = useTranslator();
    const title = localizedMediaTitle(media, locale) || t('media.untitled');

    return (
        <article className="pmc-media-library-card">
            <Link
                href={`/media-files/${media.id}`}
                className="pmc-media-library-preview"
            >
                <img
                    src={media.file_url}
                    alt={localizedMediaAlt(media, locale)}
                    loading="lazy"
                />
                <StatusBadge
                    value={media.visibility}
                    label={
                        media.visibility === 'public'
                            ? t('media.public')
                            : t('media.private')
                    }
                />
            </Link>
            <div className="pmc-media-library-copy">
                <div>
                    <span>{media.collection}</span>
                    <h3>
                        <Link href={`/media-files/${media.id}`}>{title}</Link>
                    </h3>
                    <p>{media.filename}</p>
                </div>
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
                            title,
                        })}
                    />
                </RecordActions>
            </div>
            <dl>
                <div>
                    <dt>{t('media.dimensions')}</dt>
                    <dd>{formatMediaDimensions(media)}</dd>
                </div>
                <div>
                    <dt>{t('media.size')}</dt>
                    <dd>{formatMediaBytes(media.size, locale)}</dd>
                </div>
                <div>
                    <dt>{t('media.scope_column')}</dt>
                    <dd>
                        {localizedPortfolio(
                            media,
                            locale,
                            t('media.global_website'),
                        )}
                    </dd>
                </div>
            </dl>
        </article>
    );
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
