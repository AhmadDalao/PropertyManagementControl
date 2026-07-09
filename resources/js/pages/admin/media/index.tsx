import { Head, useForm, usePage } from '@inertiajs/react';

import { DataTable, exportUrl } from '@/components/data-table';
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
    title_en?: string | null;
    path: string;
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
};

export default function MediaPage() {
    const { props } = usePage<PageProps>();
    const form = useForm({
        portfolio_id: String(
            props.auth.user?.portfolio_id ??
                props.portfolioOptions[0]?.id ??
                '',
        ),
        collection: 'default',
        title_en: '',
        title_ar: '',
        alt_text_en: '',
        alt_text_ar: '',
        visibility: 'public',
        file: null as File | null,
    });

    return (
        <AdminLayout>
            <Head title="Media" />
            <PageHeader
                title="Media"
                description="Upload and reuse visual assets for pages, banners, and supporting content."
            />

            <div className="row g-4">
                <div className="col-xl-4">
                    <div className="pmc-card p-4">
                        <form
                            className="d-grid gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.post('/media-files', {
                                    forceFormData: true,
                                    preserveScroll: true,
                                });
                            }}
                        >
                            <input
                                className="form-control"
                                placeholder="Title"
                                value={form.data.title_en}
                                onChange={(event) =>
                                    form.setData(
                                        'title_en',
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
                            <input
                                type="file"
                                className="form-control"
                                onChange={(event) =>
                                    form.setData(
                                        'file',
                                        event.currentTarget.files?.[0] ?? null,
                                    )
                                }
                            />
                            <button
                                className="btn btn-primary"
                                disabled={form.processing}
                            >
                                Upload media
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
                            exportHref={exportUrl(
                                '/exports/media-files',
                                props.filters,
                            )}
                            filterFields={[
                                {
                                    name: 'visibility',
                                    label: 'Visibility',
                                    options: [
                                        { label: 'All', value: 'all' },
                                        { label: 'Public', value: 'public' },
                                        { label: 'Private', value: 'private' },
                                    ],
                                },
                            ]}
                            columns={[
                                {
                                    key: 'title',
                                    label: 'Title',
                                    render: (item) => (
                                        <>
                                            <div className="fw-semibold">
                                                {item.title_en ??
                                                    'Untitled asset'}
                                            </div>
                                            <div className="small text-secondary">
                                                {item.collection}
                                            </div>
                                        </>
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
                                                {item.mime_type ?? '-'}
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
                                        <form
                                            onSubmit={(event) => {
                                                event.preventDefault();
                                                form.delete(
                                                    `/media-files/${item.id}`,
                                                    { preserveScroll: true },
                                                );
                                            }}
                                        >
                                            <button className="btn btn-outline-danger btn-sm">
                                                Delete
                                            </button>
                                        </form>
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
