import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

import { useTranslator } from '@/lib/i18n';

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
    section?: string;
    sectionDescription?: string;
    reloadOnChange?: { queryKey: string };
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
    tab?: 'overview' | 'financial';
    items: DetailItem[];
};

export type DecisionCard = {
    title: string;
    value: ReactNode;
    detail?: ReactNode;
    href?: string;
    actionLabel?: string;
    tone?: 'primary' | 'teal' | 'danger' | 'muted';
    icon?: string;
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

export type ResourceDetailTab =
    'overview' | 'financial' | 'documents' | 'history' | 'related';

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
    const { t, text } = useTranslator();
    const primaryActions = actions.slice(0, 2);
    const overflowActions = actions.slice(2);

    return (
        <section className="pmc-resource-header">
            <div>
                <div className="pmc-kicker mb-2">{text(eyebrow)}</div>
                <h1>{text(title)}</h1>
                {description ? <p>{text(description)}</p> : null}
            </div>
            <div className="pmc-resource-actions">
                {backHref ? (
                    <Link href={backHref} className="btn btn-light">
                        <i className="bi bi-arrow-left me-2" />
                        {text(backLabel)}
                    </Link>
                ) : null}
                {primaryActions.map((action) => (
                    <ActionLink
                        key={`${action.href}-${action.label}`}
                        action={action}
                    />
                ))}
                {overflowActions.length > 0 ? (
                    <details className="pmc-resource-action-menu">
                        <summary>
                            <i className="bi bi-three-dots" />
                            <span>
                                {t('common.more_actions', 'More actions')}
                            </span>
                        </summary>
                        <div>
                            {overflowActions.map((action) => (
                                <ActionLink
                                    key={`${action.href}-${action.label}`}
                                    action={action}
                                />
                            ))}
                        </div>
                    </details>
                ) : null}
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
    const { t, text } = useTranslator();
    const errors = Object.values(form.errors).filter(Boolean);
    const groupedFields = groupResourceFields(fields);
    const usesSections = fields.some((field) => field.section);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const options = { preserveScroll: true, forceFormData: hasFile };

        if (method === 'put') {
            form.put(action, options);

            return;
        }

        form.post(action, options);
    };

    const updateField = (field: ResourceField, value: ResourceFormValue) => {
        form.setData(field.name, value);

        if (!field.reloadOnChange) {
            return;
        }

        const queryValue =
            typeof value === 'string' || typeof value === 'number' ? value : '';

        router.get(
            window.location.pathname,
            { [field.reloadOnChange.queryKey]: queryValue },
            {
                preserveScroll: true,
                preserveState: false,
                replace: true,
            },
        );
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
                    <strong>{text('One job on this page')}</strong>
                    <p>{t('resource.form_guidance')}</p>
                </div>

                <form className="pmc-resource-form" onSubmit={submit}>
                    {errors.length > 0 ? (
                        <div
                            className="pmc-form-error-summary"
                            role="alert"
                            aria-live="assertive"
                        >
                            <i className="bi bi-exclamation-circle" />
                            <div>
                                <strong>
                                    {t('resource.validation_title')}
                                </strong>
                                <ul>
                                    {errors.map((error) => (
                                        <li key={String(error)}>
                                            {String(error)}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    ) : null}
                    {usesSections
                        ? groupedFields.map((group) => (
                              <fieldset
                                  className="pmc-resource-form-section"
                                  key={group.title}
                              >
                                  <legend>{text(group.title)}</legend>
                                  {group.description ? (
                                      <p>{text(group.description)}</p>
                                  ) : null}
                                  <div>
                                      {group.fields.map((field) => (
                                          <ResourceInput
                                              key={field.name}
                                              field={field}
                                              value={form.data[field.name]}
                                              error={String(
                                                  form.errors[field.name] ?? '',
                                              )}
                                              onChange={(value) =>
                                                  updateField(field, value)
                                              }
                                          />
                                      ))}
                                  </div>
                              </fieldset>
                          ))
                        : fields.map((field) => (
                              <ResourceInput
                                  key={field.name}
                                  field={field}
                                  value={form.data[field.name]}
                                  error={String(form.errors[field.name] ?? '')}
                                  onChange={(value) =>
                                      updateField(field, value)
                                  }
                              />
                          ))}

                    <div className="pmc-resource-form-actions">
                        <Link href={backHref} className="btn btn-light">
                            {text('Cancel')}
                        </Link>
                        <button
                            className="btn btn-primary"
                            disabled={form.processing}
                        >
                            {text(submitLabel)}
                        </button>
                    </div>
                </form>
            </section>
        </>
    );
}

function groupResourceFields(fields: ResourceField[]) {
    const groups = new Map<
        string,
        {
            title: string;
            description?: string;
            fields: ResourceField[];
        }
    >();

    fields.forEach((field) => {
        const title = field.section ?? 'Details';
        const group = groups.get(title) ?? {
            title,
            description: field.sectionDescription,
            fields: [],
        };

        group.fields.push(field);
        groups.set(title, group);
    });

    return Array.from(groups.values());
}

export function ResourceDetailShell({
    header,
    spotlight,
    decisionCards = [],
    stats = [],
    sections = [],
    related = [],
    documents = [],
    timeline = [],
}: {
    header: ResourceHeaderProps;
    spotlight?: ResourceSpotlight;
    decisionCards?: DecisionCard[];
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
    const financialSections = sections.filter(
        (section) =>
            section.tab === 'financial' ||
            (section.tab === undefined &&
                financialSectionPattern.test(section.title)),
    );
    const overviewSections = sections.filter(
        (section) => !financialSections.includes(section),
    );
    const availableTabs: Array<{
        key: ResourceDetailTab;
        label: string;
        icon: string;
    }> = [
        { key: 'overview', label: 'Overview', icon: 'bi-grid' },
        ...(financialSections.length > 0
            ? ([
                  {
                      key: 'financial',
                      label: 'Financial',
                      icon: 'bi-cash-stack',
                  },
              ] as const)
            : []),
        ...(documents.length > 0
            ? ([
                  {
                      key: 'documents',
                      label: 'Documents',
                      icon: 'bi-folder2-open',
                  },
              ] as const)
            : []),
        ...(related.length > 0
            ? ([
                  {
                      key: 'related',
                      label: 'Related',
                      icon: 'bi-diagram-3',
                  },
              ] as const)
            : []),
        ...(timeline.length > 0
            ? ([
                  {
                      key: 'history',
                      label: 'History',
                      icon: 'bi-clock-history',
                  },
              ] as const)
            : []),
    ];
    const [activeTab, setActiveTab] = useState<ResourceDetailTab>(() => {
        if (typeof window === 'undefined') {
            return 'overview';
        }

        const requested = new URLSearchParams(window.location.search).get(
            'tab',
        ) as ResourceDetailTab | null;

        return availableTabs.some((tab) => tab.key === requested)
            ? (requested ?? 'overview')
            : 'overview';
    });
    const { t, text } = useTranslator();

    const selectTab = (tab: ResourceDetailTab) => {
        setActiveTab(tab);

        if (typeof window === 'undefined') {
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...header} />

            <label className="pmc-resource-tab-select">
                <i className="bi bi-layout-text-sidebar" />
                <span className="visually-hidden">
                    {t('resource.record_sections')}
                </span>
                <select
                    className="form-select"
                    value={activeTab}
                    onChange={(event) =>
                        selectTab(
                            event.currentTarget.value as ResourceDetailTab,
                        )
                    }
                >
                    {availableTabs.map((tab) => (
                        <option key={tab.key} value={tab.key}>
                            {text(tab.label)}
                        </option>
                    ))}
                </select>
            </label>

            <nav
                className="pmc-resource-tabs"
                aria-label={t('resource.record_sections')}
            >
                {availableTabs.map((tab) => (
                    <button
                        key={tab.key}
                        type="button"
                        className={activeTab === tab.key ? 'active' : ''}
                        aria-current={
                            activeTab === tab.key ? 'page' : undefined
                        }
                        onClick={() => selectTab(tab.key)}
                    >
                        <i className={`bi ${tab.icon}`} />
                        {text(tab.label)}
                    </button>
                ))}
            </nav>

            <section className="pmc-resource-tab-panel">
                {activeTab === 'overview' ? (
                    <>
                        {spotlight ? (
                            <ResourceSpotlightPanel spotlight={spotlight} />
                        ) : null}
                        {decisionCards.length > 0 ? (
                            <DecisionCardGrid cards={decisionCards} />
                        ) : null}
                        {stats.length > 0 ? (
                            <section className="pmc-resource-stat-grid">
                                {stats.map((item) => (
                                    <article
                                        key={item.label}
                                        className={`pmc-resource-stat pmc-resource-stat-${item.tone ?? 'muted'}`}
                                    >
                                        <span>{text(item.label)}</span>
                                        <strong>{item.value ?? '-'}</strong>
                                    </article>
                                ))}
                            </section>
                        ) : null}
                        <div className="pmc-resource-detail-stack">
                            {overviewSections.map((section) => (
                                <DetailCard
                                    key={section.title}
                                    section={section}
                                />
                            ))}
                        </div>
                    </>
                ) : null}

                {activeTab === 'financial' ? (
                    <div className="pmc-resource-detail-stack">
                        {financialSections.map((section) => (
                            <DetailCard key={section.title} section={section} />
                        ))}
                    </div>
                ) : null}

                {activeTab === 'documents' ? (
                    <DocumentStrip documents={documents} />
                ) : null}

                {activeTab === 'related' ? (
                    <div className="pmc-resource-detail-stack">
                        {related.map((table) => (
                            <RelatedRecordsTable
                                key={table.title}
                                table={table}
                            />
                        ))}
                    </div>
                ) : null}

                {activeTab === 'history' ? (
                    <HistoryTimeline timeline={timeline} />
                ) : null}
            </section>
        </>
    );
}

function DecisionCardGrid({ cards }: { cards: DecisionCard[] }) {
    const { t, text } = useTranslator();

    return (
        <section className="pmc-resource-decision-grid">
            {cards.map((card) => (
                <article
                    key={card.title}
                    className={`pmc-resource-decision-card pmc-resource-decision-${card.tone ?? 'muted'}`}
                >
                    <div>
                        {card.icon ? <i className={`bi ${card.icon}`} /> : null}
                        <span>{text(card.title)}</span>
                    </div>
                    <strong>{card.value}</strong>
                    {card.detail ? (
                        <p>
                            {typeof card.detail === 'string'
                                ? text(card.detail)
                                : card.detail}
                        </p>
                    ) : null}
                    {card.href ? (
                        <Link href={card.href}>
                            {card.actionLabel
                                ? text(card.actionLabel)
                                : t('actions.open')}
                            <i className="bi bi-arrow-right" />
                        </Link>
                    ) : null}
                </article>
            ))}
        </section>
    );
}

function ResourceSpotlightPanel({
    spotlight,
}: {
    spotlight: ResourceSpotlight;
}) {
    const { text } = useTranslator();

    return (
        <section className="pmc-resource-spotlight">
            <div className="pmc-resource-spotlight-main">
                <div>
                    <div className="pmc-kicker mb-2">
                        {text(spotlight.eyebrow ?? 'Record focus')}
                    </div>
                    <h2>{text(spotlight.title)}</h2>
                    {spotlight.subtitle ? (
                        <strong>{text(spotlight.subtitle)}</strong>
                    ) : null}
                    {spotlight.description ? (
                        <p>{text(spotlight.description)}</p>
                    ) : null}
                </div>
                {spotlight.status ? <em>{text(spotlight.status)}</em> : null}
            </div>

            {spotlight.items && spotlight.items.length > 0 ? (
                <dl>
                    {spotlight.items.map((item) => (
                        <div key={item.label}>
                            <dt>{text(item.label)}</dt>
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
    const id = `pmc-field-${field.name.replaceAll(/[^a-zA-Z0-9_-]/g, '-')}`;
    const helpId = field.help ? `${id}-help` : undefined;
    const errorId = error ? `${id}-error` : undefined;
    const describedBy =
        [helpId, errorId].filter(Boolean).join(' ') || undefined;
    const { text } = useTranslator();

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
            <label className="pmc-resource-check" htmlFor={id}>
                <input
                    id={id}
                    type="checkbox"
                    checked={Boolean(value)}
                    onChange={(event) => onChange(event.currentTarget.checked)}
                />
                <span>
                    <strong>{text(field.label)}</strong>
                    {field.help ? (
                        <small id={helpId}>{text(field.help)}</small>
                    ) : null}
                </span>
                {error ? <em id={errorId}>{error}</em> : null}
            </label>
        );
    }

    return (
        <label className="pmc-resource-field" htmlFor={id}>
            <span>
                {text(field.label)}
                {field.required ? <strong>*</strong> : null}
            </span>
            {field.type === 'textarea' ? (
                <textarea
                    id={id}
                    name={field.name}
                    className="form-control"
                    rows={field.rows ?? 4}
                    value={String(value ?? '')}
                    placeholder={
                        field.placeholder ? text(field.placeholder) : undefined
                    }
                    onChange={(event) => onChange(event.currentTarget.value)}
                    aria-describedby={describedBy}
                    aria-invalid={Boolean(error)}
                />
            ) : field.type === 'select' ? (
                <select
                    id={id}
                    name={field.name}
                    className="form-select"
                    value={String(value ?? '')}
                    onChange={(event) => onChange(event.currentTarget.value)}
                    aria-describedby={describedBy}
                    aria-invalid={Boolean(error)}
                >
                    {(field.options ?? []).map((option) => (
                        <option
                            key={String(option.value)}
                            value={String(option.value)}
                        >
                            {text(option.label)}
                        </option>
                    ))}
                </select>
            ) : field.type === 'file' ? (
                <input
                    id={id}
                    name={field.name}
                    className="form-control"
                    type="file"
                    accept={field.accept}
                    onChange={(event) =>
                        onChange(event.currentTarget.files?.[0] ?? null)
                    }
                    aria-describedby={describedBy}
                    aria-invalid={Boolean(error)}
                />
            ) : (
                <input
                    id={id}
                    name={field.name}
                    className="form-control"
                    type={field.type ?? 'text'}
                    value={String(value ?? '')}
                    placeholder={
                        field.placeholder ? text(field.placeholder) : undefined
                    }
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
                    aria-describedby={describedBy}
                    aria-invalid={Boolean(error)}
                />
            )}
            {field.help ? <small id={helpId}>{text(field.help)}</small> : null}
            {error ? <em id={errorId}>{error}</em> : null}
        </label>
    );
}

function DetailCard({ section }: { section: DetailSection }) {
    const { text } = useTranslator();

    return (
        <article className="pmc-card p-4 pmc-resource-detail-card">
            <header>
                <div>
                    <div className="pmc-kicker mb-2">{text(section.title)}</div>
                    {section.description ? (
                        <p>{text(section.description)}</p>
                    ) : null}
                </div>
            </header>
            <dl>
                {section.items.map((item) => (
                    <div key={item.label}>
                        <dt>{text(item.label)}</dt>
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
    const { t, text } = useTranslator();

    return (
        <article className="pmc-card p-4 pmc-related-table-card">
            <header className="pmc-related-table-head">
                <div>
                    <div className="pmc-kicker mb-2">{text(table.title)}</div>
                    {table.description ? (
                        <p>{text(table.description)}</p>
                    ) : null}
                </div>
                {table.actionHref ? (
                    <Link
                        href={table.actionHref}
                        className="btn btn-light btn-sm"
                    >
                        {table.actionLabel
                            ? text(table.actionLabel)
                            : t('actions.open')}
                    </Link>
                ) : null}
            </header>
            {table.rows.length > 0 ? (
                <div className="pmc-table-scroll">
                    <table className="pmc-data-table table">
                        <thead>
                            <tr>
                                {table.columns.map((column) => (
                                    <th key={column}>{text(column)}</th>
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
                                                data-label={text(column)}
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
                    {table.emptyText
                        ? text(table.emptyText)
                        : t('resource.no_related_records')}
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
    const { t } = useTranslator();

    return (
        <article className="pmc-card p-4 pmc-side-panel">
            <div className="pmc-kicker mb-2">{t('common.documents')}</div>
            <h2>{t('resource.files_and_downloads')}</h2>
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
                <p className="pmc-empty-inline">{t('resource.no_documents')}</p>
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
    const { t, text } = useTranslator();

    return (
        <article className="pmc-card p-4 pmc-side-panel">
            <div className="pmc-kicker mb-2">{t('common.history')}</div>
            <h2>{t('resource.audit_trail')}</h2>
            {timeline.length > 0 ? (
                <div className="pmc-history-timeline">
                    {timeline.map((item) => (
                        <div key={item.id}>
                            <span />
                            <strong>{text(item.event)}</strong>
                            <small>
                                {item.causer ?? t('resource.system')} ·{' '}
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
                    {t('resource.no_audit_events')}
                </p>
            )}
        </article>
    );
}

function ActionLink({ action }: { action: ResourceAction }) {
    const { text } = useTranslator();
    const className = `btn btn-${action.variant === 'danger' ? 'outline-danger' : action.variant === 'primary' ? 'primary' : action.variant === 'light' ? 'light' : 'outline-secondary'}`;

    if (!action.method || action.method === 'get') {
        return (
            <Link href={action.href} className={className}>
                {text(action.label)}
            </Link>
        );
    }

    return (
        <button
            type="button"
            className={className}
            onClick={() => {
                if (action.confirm && !window.confirm(text(action.confirm))) {
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
            {text(action.label)}
        </button>
    );
}

const financialSectionPattern =
    /finance|financial|payment|rent|balance|contract|lease|expense|valuation|allocation|installment|deposit/i;
