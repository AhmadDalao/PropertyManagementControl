import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { ArchiveAction } from '@/components/archive-action';
import { CreatePageShortcut } from '@/components/create-page-shortcut';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency, humanDate } from '@/lib/utils';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type DocumentRecord = {
    id: number;
    portfolio_id: number;
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

type AssetOption = {
    id: number;
    portfolio_id: number;
    title_en: string;
    code: string;
};

type LeaseOption = {
    id: number;
    portfolio_id: number;
    code: string;
    tenant_profile?: { user?: { name: string } | null } | null;
};

type PaymentOption = {
    id: number;
    portfolio_id: number;
    lease_id?: number | null;
    reference?: string | null;
    amount: number;
    currency: string;
    lease?: { code: string } | null;
    tenant_profile?: { user?: { name: string } | null } | null;
};

type AttachmentOption = {
    id: number;
    portfolio_id: number;
    label: string;
    subtitle: string;
};

type PageProps = SharedProps & {
    documents: PaginatedData<DocumentRecord>;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    assetOptions: AssetOption[];
    leaseOptions: LeaseOption[];
    paymentOptions: PaymentOption[];
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

const attachmentTypeOptions = [
    { label: 'Lease', value: 'lease' },
    { label: 'Asset', value: 'asset' },
    { label: 'Payment', value: 'payment' },
];

export default function DocumentsPage() {
    const { props } = usePage<PageProps>();
    const isSuperadmin = Boolean(props.auth.user?.roles.includes('superadmin'));
    const defaultAttachmentType = firstAvailableAttachmentType(props);
    const defaultAttachment = attachmentOptionsForType(
        props,
        defaultAttachmentType,
    )[0];
    const documentInsights = insightFromCounts(props.counts);
    const defaultPortfolioId = String(
        isSuperadmin
            ? (defaultAttachment?.portfolio_id ??
                  props.portfolioOptions[0]?.id ??
                  '')
            : (props.auth.user?.portfolio_id ??
                  props.portfolioOptions[0]?.id ??
                  ''),
    );
    const [editing, setEditing] = useState<DocumentRecord | null>(null);
    const form = useForm({
        portfolio_id: defaultPortfolioId,
        documentable_type: defaultAttachmentType,
        documentable_id: String(defaultAttachment?.id ?? ''),
        type: 'lease_contract',
        title_en: '',
        title_ar: '',
        is_public: false,
        file: null as File | null,
    });

    const filteredAttachmentOptions = attachmentOptionsForType(
        props,
        form.data.documentable_type,
    ).filter(
        (option) =>
            !form.data.portfolio_id ||
            option.portfolio_id === Number(form.data.portfolio_id),
    );

    const startEditing = (document: DocumentRecord) => {
        const attachmentType = normalizeDocumentableType(
            document.documentable_type,
        );
        form.setData({
            portfolio_id: String(document.portfolio_id),
            documentable_type: attachmentType,
            documentable_id: String(document.documentable_id),
            type: document.type,
            title_en: document.title_en,
            title_ar: document.title_ar ?? '',
            is_public: document.is_public,
            file: null,
        });
        setEditing(document);
    };

    const clearEditing = () => {
        setEditing(null);
        form.reset();
    };

    const updateAttachmentType = (type: string) => {
        const nextOptions = attachmentOptionsForType(props, type).filter(
            (option) =>
                !form.data.portfolio_id ||
                option.portfolio_id === Number(form.data.portfolio_id),
        );

        form.setData('documentable_type', type);
        form.setData('documentable_id', String(nextOptions[0]?.id ?? ''));
    };

    const updatePortfolio = (portfolioId: string) => {
        const nextOptions = attachmentOptionsForType(
            props,
            form.data.documentable_type,
        ).filter(
            (option) =>
                !portfolioId || option.portfolio_id === Number(portfolioId),
        );

        form.setData('portfolio_id', portfolioId);
        form.setData('documentable_id', String(nextOptions[0]?.id ?? ''));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/documents/${editing.id}`, {
                preserveScroll: true,
                onSuccess: clearEditing,
            });

            return;
        }

        form.post('/documents', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset('title_en', 'title_ar', 'file'),
        });
    };

    const filterFields: TableFilterField[] = [
        {
            name: 'type',
            label: 'Type',
            options: [{ label: 'All', value: 'all' }, ...documentTypeOptions],
        },
        {
            name: 'attachment',
            label: 'Attached to',
            options: [{ label: 'All', value: 'all' }, ...attachmentTypeOptions],
        },
        {
            name: 'visibility',
            label: 'Visibility',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Public', value: 'public' },
                { label: 'Private', value: 'private' },
            ],
        },
        { name: 'date_from', label: 'From', type: 'date' },
        { name: 'date_to', label: 'To', type: 'date' },
    ];

    if (isSuperadmin) {
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

            <section className="pmc-document-command">
                <div>
                    <div className="pmc-kicker mb-3">
                        Contract and signoff control
                    </div>
                    <h1>
                        Keep every contract, receipt, and signed file attached.
                    </h1>
                    <p>
                        Documents are not a loose file cabinet. Each upload is
                        tied to a lease, asset, or payment so owners can audit
                        it and tenants only see files they are allowed to
                        download.
                    </p>
                    <CreatePageShortcut
                        href="/documents/create"
                        label="Create document"
                        icon="bi-file-earmark-plus"
                        description="Open a document form to attach signed papers, contracts, receipts, and tenant statements."
                    />
                    <div className="pmc-document-command-meta">
                        <span>
                            <i className="bi bi-file-earmark-lock" />
                            Private by default
                        </span>
                        <span>
                            <i className="bi bi-person-check" />
                            Tenant lease access scoped
                        </span>
                        <span>
                            <i className="bi bi-download" />
                            CSV export ready
                        </span>
                    </div>
                </div>

                <div className="pmc-document-insight-card">
                    <div>
                        <span>Total documents</span>
                        <strong>{documentInsights.total}</strong>
                    </div>
                    <div className="pmc-document-insight-grid">
                        <DocumentInsight
                            label="Contracts"
                            value={documentInsights.contracts}
                        />
                        <DocumentInsight
                            label="Signed"
                            value={documentInsights.signed}
                        />
                        <DocumentInsight
                            label="Receipts"
                            value={documentInsights.receipts}
                        />
                        <DocumentInsight
                            label="Portal visible"
                            value={documentInsights.public}
                        />
                    </div>
                </div>
            </section>

            <section className="pmc-document-workflow">
                {[
                    {
                        icon: 'bi-file-earmark-text',
                        title: 'Generate contract',
                        body: 'Create the lease, generate the contract PDF, then keep it attached to the lease.',
                    },
                    {
                        icon: 'bi-pen',
                        title: 'Upload signed copy',
                        body: 'Store the signed contract separately instead of overwriting generated files.',
                    },
                    {
                        icon: 'bi-receipt',
                        title: 'Attach receipts',
                        body: 'Connect receipts to payments so balances and documents agree.',
                    },
                    {
                        icon: 'bi-eye',
                        title: 'Control visibility',
                        body: 'Mark portal-visible only when the tenant should see the document.',
                    },
                ].map((item) => (
                    <div key={item.title}>
                        <i className={`bi ${item.icon}`} />
                        <strong>{item.title}</strong>
                        <span>{item.body}</span>
                    </div>
                ))}
            </section>

            <div className="row g-4">
                <div
                    className={`col-xl-4 pmc-index-form-column ${editing ? 'is-editing' : 'is-idle'}`}
                >
                    <div className="pmc-card p-4">
                        <div className="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Document form
                                </div>
                                <h2 className="h4 mb-1">
                                    {editing
                                        ? `Edit ${editing.title_en}`
                                        : 'Upload document'}
                                </h2>
                                <p className="small text-secondary mb-0">
                                    Attach every file to a lease, asset, or
                                    payment so reports and tenant access stay
                                    traceable.
                                </p>
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

                        {Object.keys(form.errors).length > 0 ? (
                            <div className="alert alert-danger py-2 small">
                                {Object.values(form.errors)[0]}
                            </div>
                        ) : null}

                        <form className="d-grid gap-3" onSubmit={submit}>
                            <div className="pmc-document-form-guide">
                                <i className="bi bi-shield-check" />
                                <div>
                                    <strong>Upload rule</strong>
                                    <span>
                                        Contracts belong on leases, ownership
                                        proof belongs on assets, and receipts
                                        belong on payments. That keeps audits
                                        clean.
                                    </span>
                                </div>
                            </div>

                            {isSuperadmin ? (
                                <div>
                                    <label className="form-label pmc-form-label">
                                        Portfolio
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.portfolio_id}
                                        onChange={(event) =>
                                            updatePortfolio(
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
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

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Attach to
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.documentable_type}
                                        onChange={(event) =>
                                            updateAttachmentType(
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        {attachmentTypeOptions.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Document type
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.type}
                                        onChange={(event) =>
                                            form.setData(
                                                'type',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        {documentTypeOptions.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">
                                    Record
                                </label>
                                <select
                                    className="form-select"
                                    value={form.data.documentable_id}
                                    disabled={
                                        filteredAttachmentOptions.length === 0
                                    }
                                    onChange={(event) =>
                                        form.setData(
                                            'documentable_id',
                                            event.currentTarget.value,
                                        )
                                    }
                                >
                                    {filteredAttachmentOptions.length > 0 ? (
                                        filteredAttachmentOptions.map(
                                            (option) => (
                                                <option
                                                    key={`${form.data.documentable_type}-${option.id}`}
                                                    value={option.id}
                                                >
                                                    {option.label}
                                                </option>
                                            ),
                                        )
                                    ) : (
                                        <option value="">
                                            No scoped records available
                                        </option>
                                    )}
                                </select>
                                {filteredAttachmentOptions.find(
                                    (option) =>
                                        String(option.id) ===
                                        form.data.documentable_id,
                                )?.subtitle ? (
                                    <p className="small text-secondary mt-2 mb-0">
                                        {
                                            filteredAttachmentOptions.find(
                                                (option) =>
                                                    String(option.id) ===
                                                    form.data.documentable_id,
                                            )?.subtitle
                                        }
                                    </p>
                                ) : null}
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">
                                    English title
                                </label>
                                <input
                                    className="form-control"
                                    placeholder="Signed contract - Unit A12"
                                    value={form.data.title_en}
                                    onChange={(event) =>
                                        form.setData(
                                            'title_en',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <label className="form-label pmc-form-label">
                                    Arabic title
                                </label>
                                <input
                                    className="form-control"
                                    placeholder="العقد الموقع - الوحدة A12"
                                    value={form.data.title_ar}
                                    onChange={(event) =>
                                        form.setData(
                                            'title_ar',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>

                            <label className="pmc-checkbox-row">
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    checked={form.data.is_public}
                                    onChange={(event) =>
                                        form.setData(
                                            'is_public',
                                            event.currentTarget.checked,
                                        )
                                    }
                                />
                                <span>
                                    Mark as portal-visible where permissions
                                    allow
                                </span>
                            </label>

                            {!editing ? (
                                <label className="pmc-document-dropzone">
                                    <i className="bi bi-cloud-arrow-up" />
                                    <span>
                                        {form.data.file?.name ??
                                            'Choose a PDF document'}
                                    </span>
                                    <small>
                                        PDF only. Maximum upload size: 10 MB.
                                    </small>
                                    <input
                                        type="file"
                                        accept=".pdf,application/pdf"
                                        onChange={(event) =>
                                            form.setData(
                                                'file',
                                                event.currentTarget
                                                    .files?.[0] ?? null,
                                            )
                                        }
                                    />
                                </label>
                            ) : (
                                <p className="small text-secondary mb-0">
                                    File replacement is disabled. Upload a new
                                    document when the signed file or receipt
                                    binary changes.
                                </p>
                            )}

                            <button
                                className="btn btn-primary"
                                disabled={
                                    form.processing ||
                                    !form.data.documentable_id ||
                                    (!editing && !form.data.file)
                                }
                            >
                                {editing
                                    ? 'Update document'
                                    : 'Upload document'}
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-xl-8">
                    <div className="pmc-card p-4">
                        <DataTable
                            title="Document library"
                            description="Search titles, original filenames, document types, and attached records."
                            data={props.documents}
                            filters={props.filters}
                            counts={props.counts}
                            basePath="/documents"
                            createHref="/documents/create"
                            createLabel="Create document"
                            rowHref={(document) => `/documents/${document.id}`}
                            exportHref={exportUrl(
                                '/exports/documents',
                                props.filters,
                            )}
                            filterFields={filterFields}
                            columns={[
                                {
                                    key: 'title',
                                    label: 'Title',
                                    render: (document) => (
                                        <div>
                                            <div className="fw-semibold">
                                                {document.title_en}
                                            </div>
                                            <div className="small text-secondary">
                                                {document.title_ar ??
                                                    document.original_name}
                                            </div>
                                            <span className="pmc-chip mt-2">
                                                {prettyLabel(document.type)}
                                            </span>
                                        </div>
                                    ),
                                },
                                {
                                    key: 'attached',
                                    label: 'Attached',
                                    render: (document) => (
                                        <div>
                                            <div className="fw-semibold">
                                                {attachmentLabel(document)}
                                            </div>
                                            <div className="small text-secondary">
                                                {document.portfolio?.name_en ??
                                                    'Portfolio'}{' '}
                                                ·{' '}
                                                {humanDate(
                                                    document.created_at,
                                                    props.app.locale,
                                                )}
                                            </div>
                                        </div>
                                    ),
                                },
                                {
                                    key: 'file',
                                    label: 'File',
                                    render: (document) => (
                                        <>
                                            <div className="text-break">
                                                {document.original_name}
                                            </div>
                                            <div className="small text-secondary">
                                                {document.mime_type ?? '-'} ·{' '}
                                                {formatBytes(
                                                    document.file_size,
                                                )}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'access',
                                    label: 'Access',
                                    render: (document) => (
                                        <div>
                                            <span
                                                className={`pmc-chip ${
                                                    document.is_public
                                                        ? 'pmc-chip--teal'
                                                        : 'pmc-chip--primary'
                                                }`}
                                            >
                                                {document.is_public
                                                    ? 'Portal visible'
                                                    : 'Private'}
                                            </span>
                                            <div className="small text-secondary mt-2">
                                                Uploaded by{' '}
                                                {document.uploaded_by?.name ??
                                                    'system'}
                                            </div>
                                        </div>
                                    ),
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    className: 'text-end',
                                    render: (document) => (
                                        <div className="d-flex justify-content-end gap-2 flex-wrap">
                                            <a
                                                className="btn btn-outline-secondary btn-sm"
                                                href={`/documents/${document.id}/download`}
                                            >
                                                Download
                                            </a>
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary btn-sm"
                                                onClick={() =>
                                                    startEditing(document)
                                                }
                                            >
                                                Edit
                                            </button>
                                            <ArchiveAction
                                                href={`/documents/${document.id}`}
                                                label="Delete"
                                                confirmMessage={`Delete ${document.title_en}? This removes the stored file.`}
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

function DocumentInsight({ label, value }: { label: string; value: number }) {
    return (
        <div>
            <span>{label}</span>
            <strong>{value}</strong>
        </div>
    );
}

function firstAvailableAttachmentType(props: PageProps): string {
    if (props.leaseOptions.length > 0) {
        return 'lease';
    }

    if (props.assetOptions.length > 0) {
        return 'asset';
    }

    return 'payment';
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

function attachmentOptionsForType(
    props: PageProps,
    type: string,
): AttachmentOption[] {
    if (type === 'asset') {
        return props.assetOptions.map((asset) => ({
            id: asset.id,
            portfolio_id: asset.portfolio_id,
            label: `${asset.code} · ${asset.title_en}`,
            subtitle: 'Asset document',
        }));
    }

    if (type === 'payment') {
        return props.paymentOptions.map((payment) => ({
            id: payment.id,
            portfolio_id: payment.portfolio_id,
            label: `${payment.reference ?? `Payment #${payment.id}`} · ${currency(
                payment.amount,
                'en',
                payment.currency,
            )}`,
            subtitle:
                payment.tenant_profile?.user?.name ??
                payment.lease?.code ??
                'Payment document',
        }));
    }

    return props.leaseOptions.map((lease) => ({
        id: lease.id,
        portfolio_id: lease.portfolio_id,
        label: lease.code,
        subtitle: lease.tenant_profile?.user?.name ?? 'Lease document',
    }));
}

function normalizeDocumentableType(type: string): string {
    if (type === 'asset' || type.endsWith('\\Asset')) {
        return 'asset';
    }

    if (type === 'payment' || type.endsWith('\\Payment')) {
        return 'payment';
    }

    return 'lease';
}

function attachmentLabel(document: DocumentRecord): string {
    const type = normalizeDocumentableType(document.documentable_type);
    const record = document.documentable ?? {};

    if (type === 'asset') {
        return String(
            record.title_en ??
                record.code ??
                `Asset #${document.documentable_id}`,
        );
    }

    if (type === 'payment') {
        return String(
            record.reference ?? `Payment #${document.documentable_id}`,
        );
    }

    return String(record.code ?? `Lease #${document.documentable_id}`);
}

function prettyLabel(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
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
