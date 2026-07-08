import { Head, useForm, usePage } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

type MediaRecord = {
    id: number;
    title_en?: string | null;
    path: string;
    collection: string;
    visibility: string;
};

type PageProps = SharedProps & {
    mediaFiles: MediaRecord[];
    portfolioOptions: Array<{ id: number; name: string }>;
};

export default function MediaPage() {
    const { props } = usePage<PageProps>();
    const form = useForm({
        portfolio_id: String(props.auth.user?.portfolio_id ?? props.portfolioOptions[0]?.id ?? ''),
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
                                onChange={(event) => form.setData('title_en', event.currentTarget.value)}
                            />
                            <input
                                type="file"
                                className="form-control"
                                onChange={(event) =>
                                    form.setData('file', event.currentTarget.files?.[0] ?? null)
                                }
                            />
                            <button className="btn btn-primary" disabled={form.processing}>
                                Upload media
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-xl-8">
                    <div className="pmc-card p-4">
                        <div className="row g-3">
                            {props.mediaFiles.map((item) => (
                                <div key={item.id} className="col-md-6">
                                    <div className="border rounded-4 p-3 h-100">
                                        <div className="fw-semibold mb-1">{item.title_en ?? 'Untitled asset'}</div>
                                        <div className="small text-secondary mb-3">{item.collection}</div>
                                        <div className="small text-break text-secondary mb-3">{item.path}</div>
                                        <form
                                            onSubmit={(event) => {
                                                event.preventDefault();
                                                form.delete(`/media-files/${item.id}`, { preserveScroll: true });
                                            }}
                                        >
                                            <button className="btn btn-outline-danger btn-sm">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
