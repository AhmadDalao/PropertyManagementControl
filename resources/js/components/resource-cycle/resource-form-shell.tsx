import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import {
    fieldError,
    groupResourceFields,
    sectionId,
} from './resource-form-helpers';
import { ResourceFormSummary } from './resource-form-summary';
import { ResourceHeader } from './resource-header';
import { ResourceInput } from './resource-input';
import type {
    ResourceField,
    ResourceFormShellProps,
    ResourceFormValue,
    ResourceFormValues,
} from './types';

export function ResourceFormShell({
    layout = 'default',
    title,
    description,
    backHref,
    backLabel,
    headerActions = [],
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
    const formId = `pmc-${layout}-resource-form`;
    const showSectionNavigation =
        usesSections && ['asset', 'lease'].includes(layout);

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
                eyebrow={t('resource.form_eyebrow', 'Record form')}
                title={title}
                description={description}
                backHref={backHref}
                backLabel={backLabel}
                actions={headerActions}
                formSubmit={{ form: formId, label: submitLabel }}
                formCancel={{ href: backHref, label: text('Cancel') }}
            />

            {showSectionNavigation ? (
                <nav
                    className={`pmc-resource-form-nav pmc-resource-form-nav-${layout}`}
                    aria-label={t('resource.form_sections', 'Form sections')}
                >
                    {groupedFields.map((group, index) => (
                        <a
                            href={`#${sectionId(group.title, index)}`}
                            key={group.title}
                        >
                            <span>{index + 1}</span>
                            {text(group.title)}
                        </a>
                    ))}
                </nav>
            ) : null}

            <section
                className={`pmc-resource-form-shell pmc-resource-form-shell-${layout}`}
                data-form-layout={layout}
            >
                <form
                    id={formId}
                    className={`pmc-resource-form pmc-resource-form-${layout}${usesSections ? '' : 'pmc-resource-form-flat'}`}
                    onSubmit={submit}
                >
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
                        ? groupedFields.map((group, index) => (
                              <fieldset
                                  className={`pmc-resource-form-section pmc-resource-form-section-${index + 1}`}
                                  key={group.title}
                                  id={sectionId(group.title, index)}
                              >
                                  <legend className="visually-hidden">
                                      {text(group.title)}
                                  </legend>
                                  <div className="pmc-resource-form-section-head">
                                      <span>{index + 1}</span>
                                      <div>
                                          <h2>{text(group.title)}</h2>
                                          {group.description ? (
                                              <p>{text(group.description)}</p>
                                          ) : null}
                                      </div>
                                  </div>
                                  <div>
                                      {group.fields.map((field) => (
                                          <ResourceInput
                                              key={field.name}
                                              field={field}
                                              value={form.data[field.name]}
                                              error={fieldError(
                                                  form.errors,
                                                  field.name,
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
                                  error={fieldError(form.errors, field.name)}
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

                <ResourceFormSummary
                    action={action}
                    description={description}
                    fields={fields}
                    layout={layout}
                    title={title}
                    values={form.data}
                />
            </section>
        </>
    );
}
