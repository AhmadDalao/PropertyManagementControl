import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { ResourceHeader } from './resource-header';
import { ResourceInput } from './resource-input';
import type {
    ResourceField,
    ResourceFormShellProps,
    ResourceFormValue,
    ResourceFormValues,
} from './types';

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
}: ResourceFormShellProps) {
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
