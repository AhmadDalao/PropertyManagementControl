import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';

export type ResourceAction = {
    label: string;
    href: string;
    method?: 'get' | 'post' | 'put' | 'delete';
    variant?: 'primary' | 'secondary' | 'danger' | 'light';
    confirm?: string;
};

export type ResourceHeaderProps = {
    eyebrow?: string;
    title: string;
    description?: string;
    backHref?: string;
    backLabel?: string;
    actions?: ResourceAction[];
};

export type ResourceField = {
    name: string;
    label: string;
    type?:
        | 'text'
        | 'email'
        | 'password'
        | 'number'
        | 'date'
        | 'textarea'
        | 'select'
        | 'checkbox'
        | 'file'
        | 'hidden';
    options?: Array<{ label: string; value: string | number | boolean }>;
    required?: boolean;
    help?: string;
    placeholder?: string;
    rows?: number;
    step?: string;
    min?: string | number;
    max?: string | number;
    accept?: string;
};

export type DetailItem = {
    label: string;
    value?: ReactNode;
    href?: string | null;
    tone?: 'primary' | 'teal' | 'danger' | 'muted';
};

export type DetailSection = {
    title: string;
    description?: string;
    items: DetailItem[];
};

export type ResourceSpotlight = {
    eyebrow?: string;
    title: string;
    subtitle?: string;
    description?: string;
    status?: string;
    items?: DetailItem[];
    actions?: ResourceAction[];
};

export type RelatedTable = {
    title: string;
    description?: string;
    columns: string[];
    rows: Array<Record<string, RelatedCell>>;
    emptyText?: string;
    actionHref?: string;
    actionLabel?: string;
};

type RelatedCell = ReactNode | { label: string; href: string };
type ResourceFormValue = string | number | boolean | File | null | undefined;
export type ResourceFormValues = Record<string, ResourceFormValue>;

export function ResourceHeader({
    eyebrow = 'Workspace',
    title,
    description,
    backHref,
    backLabel = 'Back',
    actions = [],
}: ResourceHeaderProps) {
    return (
        <section className="pmc-resource-header">
            <div>
                <div className="pmc-kicker mb-2">{eyebrow}</div>
                <h1>{title}</h1>
                {description ? <p>{description}</p> : null}
            </div>
            <div className="pmc-resource-actions">
                {backHref ? (
                    <Link href={backHref} className="btn btn-light">
                        <i className="bi bi-arrow-left me-2" />
                        {backLabel}
                    </Link>
                ) : null}
                {actions.map((action) => (
                    <ActionLink
                        key={`${action.href}-${action.label}`}
                        action={action}
                    />
                ))}
            </div>
        </section>
    );
}

export function ResourceFormShell({
    title,
    description,
    backHref,
    backLabel,
    action,
    method,
    submitLabel,
    fields,
    initialValues,
}: {
    title: string;
    description: string;
    backHref: string;
    backLabel: string;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    fields: ResourceField[];
    initialValues: ResourceFormValues;
}) {
    const form = useForm<ResourceFormValues>(initialValues);
    const hasFile = fields.some((field) => field.type === 'file');

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const options = { preserveScroll: true, forceFormData: hasFile };

        if (method === 'put') {
            form.put(action, options);

            return;
        }

        form.post(action, options);
    };

    return (
        <>
            <ResourceHeader
                eyebrow="Focused input"
                title={title}
                description={description}
                backHref={backHref}
                backLabel={backLabel}
            />

            <section className="pmc-resource-form-shell">
                <div className="pmc-resource-form-brief">
                    <span className="pmc-table-icon">
                        <i className="bi bi-pencil-square" />
                    </span>
                    <strong>One job on this page</strong>
                    <p>
                        Fill the record cleanly, save it, then review the detail
                        page for documents, history, and next actions.
                    </p>
                </div>

                <form className="pmc-resource-form" onSubmit={submit}>
                    {fields.map((field) => (
                        <ResourceInput
                            key={field.name}
                            field={field}
                            value={form.data[field.name]}
                            error={String(form.errors[field.name] ?? '')}
                            onChange={(value) =>
                                form.setData(field.name, value)
                            }
                        />
                    ))}

                    <div className="pmc-resource-form-actions">
                        <Link href={backHref} className="btn btn-light">
                            Cancel
                        </Link>
                        <button
                            className="btn btn-primary"
                            disabled={form.processing}
                        >
                            {submitLabel}
                        </button>
                    </div>
                </form>
            </section>
        </>
    );
}

export function ResourceDetailShell({
    header,
    spotlight,
    stats = [],
    sections = [],
    related = [],
    documents = [],
    timeline = [],
}: {
    header: ResourceHeaderProps;
    spotlight?: ResourceSpotlight;
    stats?: DetailItem[];
    sections?: DetailSection[];
    related?: RelatedTable[];
    documents?: Array<{
        id: number;
        title: string;
        subtitle?: string;
        badge?: string;
        href: string;
    }>;
    timeline?: Array<{
        id: number;
        event: string;
        description?: string;
        causer?: string;
        created_at?: string;
    }>;
}) {
    return (
        <>
            <ResourceHeader {...header} />

            {spotlight ? (
                <ResourceSpotlightPanel spotlight={spotlight} />
            ) : null}

            {stats.length > 0 ? (
                <section className="pmc-resource-stat-grid">
                    {stats.map((item) => (
                        <article
                            key={item.label}
                            className={`pmc-resource-stat pmc-resource-stat-${item.tone ?? 'muted'}`}
                        >
                            <span>{item.label}</span>
                            <strong>{item.value ?? '-'}</strong>
                        </article>
                    ))}
                </section>
            ) : null}

            <section className="pmc-resource-detail-layout">
                <div className="pmc-resource-detail-stack">
                    {sections.map((section) => (
                        <DetailCard key={section.title} section={section} />
                    ))}

                    {related.map((table) => (
                        <RelatedRecordsTable key={table.title} table={table} />
                    ))}
                </div>

                <aside className="pmc-resource-side-stack">
                    <DocumentStrip documents={documents} />
                    <HistoryTimeline timeline={timeline} />
                </aside>
            </section>
        </>
    );
}

function ResourceSpotlightPanel({
    spotlight,
}: {
    spotlight: ResourceSpotlight;
}) {
    return (
        <section className="pmc-resource-spotlight">
            <div className="pmc-resource-spotlight-main">
                <div>
                    <div className="pmc-kicker mb-2">
                        {spotlight.eyebrow ?? 'Record focus'}
                    </div>
                    <h2>{spotlight.title}</h2>
                    {spotlight.subtitle ? (
                        <strong>{spotlight.subtitle}</strong>
                    ) : null}
                    {spotlight.description ? (
                        <p>{spotlight.description}</p>
                    ) : null}
                </div>
                {spotlight.status ? <em>{spotlight.status}</em> : null}
            </div>

            {spotlight.items && spotlight.items.length > 0 ? (
                <dl>
                    {spotlight.items.map((item) => (
                        <div key={item.label}>
                            <dt>{item.label}</dt>
                            <dd>
                                {item.href ? (
                                    <Link href={item.href}>
                                        {item.value ?? '-'}
                                    </Link>
                                ) : (
                                    (item.value ?? '-')
                                )}
                            </dd>
                        </div>
                    ))}
                </dl>
            ) : null}

            {spotlight.actions && spotlight.actions.length > 0 ? (
                <div className="pmc-resource-spotlight-actions">
                    {spotlight.actions.map((action) => (
                        <ActionLink
                            key={`${action.href}-${action.label}`}
                            action={action}
                        />
                    ))}
                </div>
            ) : null}
        </section>
    );
}

function ResourceInput({
    field,
    value,
    error,
    onChange,
}: {
    field: ResourceField;
    value: ResourceFormValue;
    error?: string;
    onChange: (value: ResourceFormValue) => void;
}) {
    if (field.type === 'hidden') {
        return (
            <input
                type="hidden"
                name={field.name}
                value={String(value ?? '')}
            />
        );
    }

    if (field.type === 'checkbox') {
        return (
            <label className="pmc-resource-check">
                <input
                    type="checkbox"
                    checked={Boolean(value)}
                    onChange={(event) => onChange(event.currentTarget.checked)}
                />
                <span>
                    <strong>{field.label}</strong>
                    {field.help ? <small>{field.help}</small> : null}
                </span>
                {error ? <em>{error}</em> : null}
            </label>
        );
    }

    return (
        <label className="pmc-resource-field">
            <span>
                {field.label}
                {field.required ? <strong>*</strong> : null}
            </span>
            {field.type === 'textarea' ? (
                <textarea
                    className="form-control"
                    rows={field.rows ?? 4}
                    value={String(value ?? '')}
                    placeholder={field.placeholder}
                    onChange={(event) => onChange(event.currentTarget.value)}
                />
            ) : field.type === 'select' ? (
                <select
                    className="form-select"
                    value={String(value ?? '')}
                    onChange={(event) => onChange(event.currentTarget.value)}
                >
                    {(field.options ?? []).map((option) => (
                        <option
                            key={String(option.value)}
                            value={String(option.value)}
                        >
                            {option.label}
                        </option>
                    ))}
                </select>
            ) : field.type === 'file' ? (
                <input
                    className="form-control"
                    type="file"
                    accept={field.accept}
                    onChange={(event) =>
                        onChange(event.currentTarget.files?.[0] ?? null)
                    }
                />
            ) : (
                <input
                    className="form-control"
                    type={field.type ?? 'text'}
                    value={String(value ?? '')}
                    placeholder={field.placeholder}
                    step={field.step}
                    min={field.min}
                    max={field.max}
                    onChange={(event) =>
                        onChange(
                            field.type === 'number'
                                ? event.currentTarget.value === ''
                                    ? ''
                                    : Number(event.currentTarget.value)
                                : event.currentTarget.value,
                        )
                    }
                />
            )}
            {field.help ? <small>{field.help}</small> : null}
            {error ? <em>{error}</em> : null}
        </label>
    );
}

function DetailCard({ section }: { section: DetailSection }) {
    return (
        <article className="pmc-card p-4 pmc-resource-detail-card">
            <header>
                <div>
                    <div className="pmc-kicker mb-2">{section.title}</div>
                    {section.description ? <p>{section.description}</p> : null}
                </div>
            </header>
            <dl>
                {section.items.map((item) => (
                    <div key={item.label}>
                        <dt>{item.label}</dt>
                        <dd>
                            {item.href ? (
                                <Link href={item.href}>
                                    {item.value ?? '-'}
                                </Link>
                            ) : (
                                (item.value ?? '-')
                            )}
                        </dd>
                    </div>
                ))}
            </dl>
        </article>
    );
}

function RelatedRecordsTable({ table }: { table: RelatedTable }) {
    return (
        <article className="pmc-card p-4 pmc-related-table-card">
            <header className="pmc-related-table-head">
                <div>
                    <div className="pmc-kicker mb-2">{table.title}</div>
                    {table.description ? <p>{table.description}</p> : null}
                </div>
                {table.actionHref ? (
                    <Link
                        href={table.actionHref}
                        className="btn btn-light btn-sm"
                    >
                        {table.actionLabel ?? 'Open'}
                    </Link>
                ) : null}
            </header>
            {table.rows.length > 0 ? (
                <div className="pmc-table-scroll">
                    <table className="pmc-data-table table">
                        <thead>
                            <tr>
                                {table.columns.map((column) => (
                                    <th key={column}>{column}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {table.rows.map((row, index) => (
                                <tr key={index}>
                                    {table.columns.map((column) => {
                                        const value = row[column] ?? '-';

                                        return (
                                            <td
                                                key={column}
                                                data-label={column}
                                            >
                                                {isRelatedCellLink(value) ? (
                                                    <Link href={value.href}>
                                                        {value.label}
                                                    </Link>
                                                ) : (
                                                    value
                                                )}
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <p className="pmc-empty-inline">
                    {table.emptyText ?? 'No related records yet.'}
                </p>
            )}
        </article>
    );
}

function isRelatedCellLink(
    value: RelatedCell,
): value is { label: string; href: string } {
    return Boolean(
        value &&
        typeof value === 'object' &&
        !Array.isArray(value) &&
        'href' in value &&
        'label' in value,
    );
}

function DocumentStrip({
    documents,
}: {
    documents: Array<{
        id: number;
        title: string;
        subtitle?: string;
        badge?: string;
        href: string;
    }>;
}) {
    return (
        <article className="pmc-card p-4 pmc-side-panel">
            <div className="pmc-kicker mb-2">Documents</div>
            <h2>Files and downloads</h2>
            {documents.length > 0 ? (
                <div className="pmc-document-strip">
                    {documents.map((document) => (
                        <a key={document.id} href={document.href}>
                            <i className="bi bi-file-earmark-arrow-down" />
                            <span>
                                <strong>{document.title}</strong>
                                <small>{document.subtitle}</small>
                            </span>
                            {document.badge ? <em>{document.badge}</em> : null}
                        </a>
                    ))}
                </div>
            ) : (
                <p className="pmc-empty-inline">No documents attached yet.</p>
            )}
        </article>
    );
}

function HistoryTimeline({
    timeline,
}: {
    timeline: Array<{
        id: number;
        event: string;
        description?: string;
        causer?: string;
        created_at?: string;
    }>;
}) {
    return (
        <article className="pmc-card p-4 pmc-side-panel">
            <div className="pmc-kicker mb-2">History</div>
            <h2>Audit trail</h2>
            {timeline.length > 0 ? (
                <div className="pmc-history-timeline">
                    {timeline.map((item) => (
                        <div key={item.id}>
                            <span />
                            <strong>{item.event}</strong>
                            <small>
                                {item.causer ?? 'System'} ·{' '}
                                {item.created_at ?? ''}
                            </small>
                            {item.description ? (
                                <p>{item.description}</p>
                            ) : null}
                        </div>
                    ))}
                </div>
            ) : (
                <p className="pmc-empty-inline">
                    No audit events recorded yet.
                </p>
            )}
        </article>
    );
}

function ActionLink({ action }: { action: ResourceAction }) {
    const className = `btn btn-${action.variant === 'danger' ? 'outline-danger' : action.variant === 'primary' ? 'primary' : action.variant === 'light' ? 'light' : 'outline-secondary'}`;

    if (!action.method || action.method === 'get') {
        return (
            <Link href={action.href} className={className}>
                {action.label}
            </Link>
        );
    }

    return (
        <button
            type="button"
            className={className}
            onClick={() => {
                if (action.confirm && !window.confirm(action.confirm)) {
                    return;
                }

                if (action.method === 'delete') {
                    router.delete(action.href, { preserveScroll: true });

                    return;
                }

                if (action.method === 'put') {
                    router.put(action.href, {}, { preserveScroll: true });

                    return;
                }

                router.post(action.href, {}, { preserveScroll: true });
            }}
        >
            {action.label}
        </button>
    );
}
