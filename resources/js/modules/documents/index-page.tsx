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
import { humanDate } from '@/lib/utils';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type DocumentRecord = {
    id: number;
    documentable_type: string;
    documentable_id: number;
    type: string;
    title_en: string;
    title_ar?: string | null;
    original_name: string;
    mime_type?: string | null;
    file_size: number;
    is_public: boolean;
    created_at: string;
    portfolio?: { name_en: string } | null;
    uploaded_by?: { name: string } | null;
    documentable?: Record<string, unknown> | null;
};

type PageProps = SharedProps & {
    documents: PaginatedData<DocumentRecord>;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
};

const documentTypeOptions = [
    { label: 'Lease contract', value: 'lease_contract' },
    { label: 'Signed contract', value: 'signed_contract' },
    { label: 'Receipt', value: 'receipt' },
    { label: 'Owner report', value: 'owner_report' },
    { label: 'Tenant statement', value: 'tenant_statement' },
    { label: 'Identity document', value: 'identity_document' },
    { label: 'Other', value: 'other' },
];

export default function DocumentsIndexPage() {
    const { props } = usePage<PageProps>();
    const insights = insightFromCounts(props.counts);
    const filterFields: TableFilterField[] = [
        {
            name: 'type',
            label: 'Type',
            options: [{ label: 'All', value: 'all' }, ...documentTypeOptions],
        },
        {
            name: 'attachment',
            label: 'Attached to',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Lease', value: 'lease' },
                { label: 'Asset', value: 'asset' },
                { label: 'Payment', value: 'payment' },
            ],
        },
        {
            name: 'visibility',
            label: 'Visibility',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Portal visible', value: 'public' },
                { label: 'Private', value: 'private' },
            ],
        },
        { name: 'date_from', label: 'From', type: 'date' },
        { name: 'date_to', label: 'To', type: 'date' },
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
            <Head title="Documents" />

            <WorkspaceHeader
                eyebrow="Money & service"
                title="Documents"
                description="Every uploaded paper is a PDF attached to a lease, asset, or payment with explicit tenant visibility."
                actions={[
                    {
                        label: 'Upload PDF',
                        href: '/documents/create',
                        icon: 'bi-file-earmark-plus',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Documents',
                        value: insights.total,
                        detail: 'Files in the current scope',
                        icon: 'bi-folder2-open',
                        tone: 'ink',
                    },
                    {
                        label: 'Contracts',
                        value: insights.contracts,
                        detail: `${insights.signed} signed contracts`,
                        icon: 'bi-file-earmark-text',
                        tone: 'blue',
                    },
                    {
                        label: 'Receipts',
                        value: insights.receipts,
                        detail: 'Payment proof PDFs',
                        icon: 'bi-receipt',
                        tone: 'teal',
                    },
                    {
                        label: 'Portal visible',
                        value: insights.public,
                        detail: 'Available to authorized tenants',
                        icon: 'bi-eye',
                        tone: 'amber',
                    },
                ]}
            />

            <DataTable
                title="Document register"
                description="Search title, original filename, type, attached record, or uploader."
                data={props.documents}
                filters={props.filters}
                counts={props.counts}
                basePath="/documents"
                rowHref={(document) => `/documents/${document.id}`}
                exportHref={exportUrl('/exports/documents', props.filters)}
                filterFields={filterFields}
                columns={[
                    {
                        key: 'title',
                        label: 'Document',
                        render: (document) => (
                            <div className="pmc-primary-cell">
                                <strong>{document.title_en}</strong>
                                <span>
                                    {document.title_ar ??
                                        document.original_name}
                                </span>
                                <StatusBadge
                                    value={document.type}
                                    tone="blue"
                                />
                            </div>
                        ),
                    },
                    {
                        key: 'attached',
                        label: 'Attached to',
                        render: (document) => (
                            <div className="pmc-stacked-cell">
                                <strong>{attachmentLabel(document)}</strong>
                                <span>
                                    {document.portfolio?.name_en ?? 'Portfolio'}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'file',
                        label: 'File',
                        render: (document) => (
                            <div className="pmc-stacked-cell">
                                <strong>{document.original_name}</strong>
                                <span>
                                    PDF · {formatBytes(document.file_size)}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'access',
                        label: 'Access',
                        render: (document) => (
                            <div className="pmc-stacked-cell">
                                <StatusBadge
                                    value={
                                        document.is_public
                                            ? 'Portal visible'
                                            : 'Private'
                                    }
                                    tone={
                                        document.is_public
                                            ? 'success'
                                            : 'neutral'
                                    }
                                />
                                <span>
                                    Uploaded by{' '}
                                    {document.uploaded_by?.name ?? 'System'} ·{' '}
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
                        label: 'Actions',
                        className: 'text-end',
                        render: (document) => (
                            <RecordActions
                                showHref={`/documents/${document.id}`}
                                editHref={`/documents/${document.id}/edit`}
                            >
                                <a
                                    href={`/documents/${document.id}/download`}
                                    className="btn btn-outline-secondary btn-sm"
                                >
                                    <i className="bi bi-download" />
                                    <span>PDF</span>
                                </a>
                                <ArchiveAction
                                    href={`/documents/${document.id}`}
                                    label="Delete"
                                    confirmMessage={`Delete ${document.title_en}? This removes the stored file.`}
                                />
                            </RecordActions>
                        ),
                    },
                ]}
            />
        </AdminLayout>
    );
}

function insightFromCounts(counts: TableCount[]) {
    const valueFor = (label: string) =>
        counts.find((count) => count.label === label)?.value ?? 0;

    return {
        total: valueFor('All'),
        contracts: valueFor('Lease contracts'),
        signed: valueFor('Signed'),
        receipts: valueFor('Receipts'),
        public: valueFor('Public'),
    };
}

function attachmentLabel(document: DocumentRecord): string {
    const record = document.documentable ?? {};
    const type = document.documentable_type;

    if (type === 'asset' || type.endsWith('\\Asset')) {
        return String(
            record.title_en ??
                record.code ??
                `Asset #${document.documentable_id}`,
        );
    }

    if (type === 'payment' || type.endsWith('\\Payment')) {
        return String(
            record.reference ?? `Payment #${document.documentable_id}`,
        );
    }

    return String(record.code ?? `Lease #${document.documentable_id}`);
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
