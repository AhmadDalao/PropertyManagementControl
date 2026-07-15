import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { ArchiveAction } from '@/components/archive-action';
import { CreatePageShortcut } from '@/components/create-page-shortcut';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type MediaRecord = {
    id: number;
    portfolio_id?: number | null;
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

export default function MediaPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<MediaRecord | null>(null);
    const form = useForm({
        portfolio_id: String(
            props.auth.user?.roles.includes('superadmin')
                ? ''
                : (props.auth.user?.portfolio_id ??
                      props.portfolioOptions[0]?.id ??
                      ''),
        ),
        collection: 'default',
        title_en: '',
        title_ar: '',
        alt_text_en: '',
        alt_text_ar: '',
        visibility: 'public',
        file: null as File | null,
    });

    const startEditing = (item: MediaRecord) => {
        form.setData({
            portfolio_id: item.portfolio_id ? String(item.portfolio_id) : '',
            collection: item.collection,
            title_en: item.title_en ?? '',
            title_ar: item.title_ar ?? '',
            alt_text_en: item.alt_text_en ?? '',
            alt_text_ar: item.alt_text_ar ?? '',
            visibility: item.visibility,
            file: null,
        });
        setEditing(item);
    };

    const clearEditing = () => {
        setEditing(null);
        form.reset();
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/media-files/${editing.id}`, {
                preserveScroll: true,
                onSuccess: clearEditing,
            });

            return;
        }

        form.post('/media-files', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset('file', 'title_en', 'title_ar'),
        });
    };

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
            <Head title="Media" />
            <PageHeader
                title="Media"
                description="Upload and reuse visual assets for pages, banners, and supporting content."
            />

            <CreatePageShortcut
                href="/media-files/create"
                label="Create media"
                icon="bi-image"
                description="Open a media form to upload an image or file with collection, visibility, and bilingual alt text."
            />

            <div className="row g-4">
                <div
                    className={`col-xl-4 pmc-index-form-column ${editing ? 'is-editing' : 'is-idle'}`}
                >
                    <div className="pmc-card p-4">
                        <div className="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Media form
                                </div>
                                <h2 className="h4 mb-0">
                                    {editing
                                        ? `Edit ${editing.title_en ?? `Media #${editing.id}`}`
                                        : 'Upload media'}
                                </h2>
                            </div>
                            {editing ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={clearEditing}
                                >
                                    Reset
                                </button>
                            ) : null}
                        </div>

                        {editing && isImage(editing) ? (
                            <img
                                className="pmc-media-preview mb-3"
                                src={mediaUrl(editing)}
                                alt={editing.alt_text_en ?? ''}
                            />
                        ) : null}

                        <form className="d-grid gap-3" onSubmit={submit}>
                            {props.auth.user?.roles.includes('superadmin') ? (
                                <div>
                                    <label className="form-label pmc-form-label">
                                        Portfolio
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.portfolio_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'portfolio_id',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="">
                                            Global website media
                                        </option>
                                        {props.portfolioOptions.map(
                                            (portfolio) => (
                                                <option
                                                    key={portfolio.id}
                                                    value={portfolio.id}
                                                >
                                                    {portfolio.name}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </div>
                            ) : null}

                            <input
                                className="form-control"
                                placeholder="English title"
                                value={form.data.title_en}
                                onChange={(event) =>
                                    form.setData(
                                        'title_en',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <input
                                className="form-control"
                                placeholder="Arabic title"
                                value={form.data.title_ar}
                                onChange={(event) =>
                                    form.setData(
                                        'title_ar',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <div className="row g-3">
                                <div className="col-md-6">
                                    <input
                                        className="form-control"
                                        placeholder="Collection"
                                        value={form.data.collection}
                                        onChange={(event) =>
                                            form.setData(
                                                'collection',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <select
                                        className="form-select"
                                        value={form.data.visibility}
                                        onChange={(event) =>
                                            form.setData(
                                                'visibility',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="public">Public</option>
                                        <option value="private">Private</option>
                                    </select>
                                </div>
                            </div>
                            <textarea
                                className="form-control"
                                rows={2}
                                placeholder="English alt text"
                                value={form.data.alt_text_en}
                                onChange={(event) =>
                                    form.setData(
                                        'alt_text_en',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <textarea
                                className="form-control"
                                rows={2}
                                placeholder="Arabic alt text"
                                value={form.data.alt_text_ar}
                                onChange={(event) =>
                                    form.setData(
                                        'alt_text_ar',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            {!editing ? (
                                <input
                                    type="file"
                                    className="form-control"
                                    onChange={(event) =>
                                        form.setData(
                                            'file',
                                            event.currentTarget.files?.[0] ??
                                                null,
                                        )
                                    }
                                />
                            ) : (
                                <p className="small text-secondary mb-0">
                                    File replacement is intentionally disabled.
                                    Upload a new asset when the binary changes
                                    so old CMS references do not silently break.
                                </p>
                            )}
                            <button
                                className="btn btn-primary"
                                disabled={form.processing}
                            >
                                {editing ? 'Update media' : 'Upload media'}
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-xl-8">
                    <div className="pmc-card p-4">
                        <DataTable
                            title="Media library"
                            description="Search titles, collection names, file paths, and MIME types."
                            data={props.mediaFiles}
                            filters={props.filters}
                            counts={props.counts}
                            basePath="/media-files"
                            createHref="/media-files/create"
                            createLabel="Create media"
                            rowHref={(mediaFile) =>
                                `/media-files/${mediaFile.id}`
                            }
                            exportHref={exportUrl(
                                '/exports/media-files',
                                props.filters,
                            )}
                            filterFields={filterFields}
                            columns={[
                                {
                                    key: 'title',
                                    label: 'Title',
                                    render: (item) => (
                                        <div className="d-flex gap-3 align-items-center">
                                            {isImage(item) ? (
                                                <img
                                                    className="pmc-media-thumb"
                                                    src={mediaUrl(item)}
                                                    alt={item.alt_text_en ?? ''}
                                                />
                                            ) : (
                                                <span className="pmc-media-file-icon">
                                                    <i className="bi bi-file-earmark" />
                                                </span>
                                            )}
                                            <div>
                                                <div className="fw-semibold">
                                                    {item.title_en ??
                                                        'Untitled asset'}
                                                </div>
                                                <div className="small text-secondary">
                                                    {item.title_ar ??
                                                        item.collection}
                                                </div>
                                                <span className="pmc-chip mt-2">
                                                    {item.collection}
                                                </span>
                                            </div>
                                        </div>
                                    ),
                                },
                                {
                                    key: 'file',
                                    label: 'File',
                                    render: (item) => (
                                        <>
                                            <div className="text-break">
                                                {item.path}
                                            </div>
                                            <div className="small text-secondary">
                                                {item.mime_type ?? '-'} ·{' '}
                                                {formatBytes(item.size)}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'visibility',
                                    label: 'Visibility',
                                    render: (item) => (
                                        <span className="pmc-chip pmc-chip--primary">
                                            {item.visibility}
                                        </span>
                                    ),
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    className: 'text-end',
                                    render: (item) => (
                                        <div className="d-flex justify-content-end gap-2 flex-wrap">
                                            <a
                                                className="btn btn-outline-secondary btn-sm"
                                                href={mediaUrl(item)}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                Open
                                            </a>
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary btn-sm"
                                                onClick={() =>
                                                    startEditing(item)
                                                }
                                            >
                                                Edit
                                            </button>
                                            <ArchiveAction
                                                href={`/media-files/${item.id}`}
                                                label="Delete"
                                                confirmMessage={`Delete ${item.title_en ?? item.path}? This removes the file from storage.`}
                                            />
                                        </div>
                                    ),
                                },
                            ]}
                        />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

function isImage(item: MediaRecord): boolean {
    return Boolean(item.mime_type?.startsWith('image/'));
}

function mediaUrl(item: MediaRecord): string {
    if (item.disk !== 'public') {
        return '#';
    }

    return `/storage/${item.path}`;
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
