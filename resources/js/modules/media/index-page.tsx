import { Head, usePage } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import {
    MetricGrid,
    RecordActions,
    StatusBadge,
    WorkspaceHeader,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type MediaRecord = {
    id: number;
    title_en?: string | null;
    title_ar?: string | null;
    alt_text_en?: string | null;
    alt_text_ar?: string | null;
    path: string;
    disk: string;
    collection: string;
    visibility: string;
    mime_type?: string | null;
    size: number;
};

type PageProps = SharedProps & {
    mediaFiles: PaginatedData<MediaRecord>;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    collectionOptions: string[];
};

export default function MediaIndexPage() {
    const { props } = usePage<PageProps>();
    const { locale, t, text } = useTranslator();
    const filterFields: TableFilterField[] = [
        {
            name: 'visibility',
            label: 'Visibility',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Public', value: 'public' },
                { label: 'Private', value: 'private' },
            ],
        },
        {
            name: 'collection',
            label: 'Collection',
            options: [
                { label: 'All', value: 'all' },
                ...props.collectionOptions.map((collection) => ({
                    label: collection,
                    value: collection,
                })),
            ],
        },
    ];

    if (props.auth.user?.roles.includes('superadmin')) {
        filterFields.push({
            name: 'portfolio_id',
            label: 'Portfolio',
            options: [
                { label: 'All', value: 'all' },
                ...props.portfolioOptions.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return (
        <AdminLayout>
            <Head title={text('Media Library')} />

            <WorkspaceHeader
                eyebrow="System"
                title="Media library"
                description="Upload reusable images and files, add bilingual alt text, and control which assets are public."
                actions={[
                    {
                        label: 'Upload media',
                        href: '/media-files/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Media files',
                        value: props.mediaFiles.total,
                        detail: 'All files in the current scope',
                        icon: 'bi-images',
                        tone: 'ink',
                    },
                    {
                        label: 'Public',
                        value: countValue(props.counts, 'Public'),
                        detail: 'Available to published pages',
                        icon: 'bi-globe2',
                        tone: 'teal',
                    },
                    {
                        label: 'Private',
                        value: countValue(props.counts, 'Private'),
                        detail: 'Restricted media files',
                        icon: 'bi-lock',
                        tone: 'amber',
                    },
                    {
                        label: 'Collections',
                        value: props.collectionOptions.length,
                        detail: 'Reusable media groups',
                        icon: 'bi-collection',
                        tone: 'blue',
                    },
                ]}
            />

            <DataTable
                title="Media register"
                description="Search title, alt text, path, collection, or file type."
                data={props.mediaFiles}
                filters={props.filters}
                counts={props.counts}
                basePath="/media-files"
                rowHref={(media) => `/media-files/${media.id}`}
                exportHref={exportUrl('/exports/media-files', props.filters)}
                filterFields={filterFields}
                columns={[
                    {
                        key: 'title',
                        label: 'Media',
                        render: (media) => (
                            <div className="pmc-media-primary-cell">
                                {isImage(media) ? (
                                    <img
                                        className="pmc-media-thumb"
                                        src={mediaUrl(media)}
                                        alt={
                                            locale === 'ar'
                                                ? media.alt_text_ar ||
                                                  media.alt_text_en ||
                                                  ''
                                                : media.alt_text_en ||
                                                  media.alt_text_ar ||
                                                  ''
                                        }
                                    />
                                ) : (
                                    <span className="pmc-media-file-icon">
                                        <i className="bi bi-file-earmark" />
                                    </span>
                                )}
                                <div className="pmc-primary-cell">
                                    <strong>
                                        {(locale === 'ar'
                                            ? media.title_ar || media.title_en
                                            : media.title_en ||
                                              media.title_ar) ??
                                            text('Untitled media')}
                                    </strong>
                                    <span>
                                        {media.title_ar ?? media.collection}
                                    </span>
                                </div>
                            </div>
                        ),
                    },
                    {
                        key: 'file',
                        label: 'File',
                        render: (media) => (
                            <div className="pmc-stacked-cell">
                                <strong>{media.path}</strong>
                                <span>
                                    {media.mime_type ?? text('Unknown type')} ·{' '}
                                    {formatBytes(media.size)}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'collection',
                        label: 'Collection',
                        render: (media) => (
                            <StatusBadge value={media.collection} tone="blue" />
                        ),
                    },
                    {
                        key: 'visibility',
                        label: 'Visibility',
                        render: (media) => (
                            <StatusBadge value={media.visibility} />
                        ),
                    },
                    {
                        key: 'actions',
                        label: 'Actions',
                        className: 'text-end',
                        render: (media) => (
                            <RecordActions
                                showHref={`/media-files/${media.id}`}
                                editHref={`/media-files/${media.id}/edit`}
                            >
                                {media.disk === 'public' ? (
                                    <a
                                        className="btn btn-outline-secondary btn-sm"
                                        href={mediaUrl(media)}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        <i className="bi bi-box-arrow-up-right" />
                                        <span>{text('File')}</span>
                                    </a>
                                ) : null}
                                <ArchiveAction
                                    href={`/media-files/${media.id}`}
                                    label="Delete"
                                    confirmMessage={t(
                                        'media.delete_confirm',
                                        undefined,
                                        {
                                            title:
                                                (locale === 'ar'
                                                    ? media.title_ar ||
                                                      media.title_en
                                                    : media.title_en ||
                                                      media.title_ar) ??
                                                media.path,
                                        },
                                    )}
                                />
                            </RecordActions>
                        ),
                    },
                ]}
            />
        </AdminLayout>
    );
}

function countValue(counts: TableCount[], label: string) {
    return counts.find((count) => count.label === label)?.value ?? 0;
}

function isImage(media: MediaRecord) {
    return Boolean(media.mime_type?.startsWith('image/'));
}

function mediaUrl(media: MediaRecord) {
    return media.disk === 'public' ? `/storage/${media.path}` : '#';
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
