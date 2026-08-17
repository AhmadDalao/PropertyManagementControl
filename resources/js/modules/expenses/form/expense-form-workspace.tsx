import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { ResourceHeader } from '@/components/resource-cycle';
import type { ResourceField } from '@/components/resource-cycle';
import {
    groupResourceFields,
    sectionId,
} from '@/components/resource-cycle/resource-form-helpers';
import type { ResourceFormValue } from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

import type { ExpenseFormPage } from '../types';
import { ExpenseFormGuide } from './expense-form-guide';
import { ExpenseFormSection } from './expense-form-section';

export function ExpenseFormWorkspace({ page }: { page: ExpenseFormPage }) {
    const form = useForm(page.initialValues);
    const groups = groupResourceFields(page.fields);
    const { t, text } = useTranslator();
    const formId = 'pmc-expense-form';
    const errors = Object.values(form.errors).filter(Boolean);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (page.method === 'put') {
            form.put(page.action, { preserveScroll: true });
        } else {
            form.post(page.action, { preserveScroll: true });
        }
    };

    const updateField = (field: ResourceField, value: ResourceFormValue) => {
        form.setData(field.name, value);

        if (!field.reloadOnChange) {
            return;
        }

        const locale = new URLSearchParams(window.location.search).get(
            'locale',
        );
        const queryValue =
            typeof value === 'string' || typeof value === 'number' ? value : '';

        router.get(
            window.location.pathname,
            {
                [field.reloadOnChange.queryKey]: queryValue,
                ...(locale ? { locale } : {}),
            },
            { preserveScroll: true, preserveState: false, replace: true },
        );
    };

    return (
        <div className="pmc-expense-form-page">
            <ResourceHeader
                eyebrow={t('expenses.form_eyebrow')}
                title={page.title}
                description={page.description}
                backHref={page.backHref}
                backLabel={page.backLabel}
                formCancel={{ href: page.backHref, label: t('actions.cancel') }}
                formSubmit={{ form: formId, label: page.submitLabel }}
            />

            <section className="pmc-expense-form-layout">
                <ExpenseFormGuide
                    mode={page.mode}
                    context={page.context}
                    fields={page.fields}
                    values={form.data}
                />

                <form id={formId} onSubmit={submit}>
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

                    {groups.map((group, index) => (
                        <div
                            id={sectionId(group.title, index)}
                            key={group.title}
                        >
                            <ExpenseFormSection
                                number={index + 1}
                                title={group.title}
                                description={group.description}
                                fields={group.fields}
                                values={form.data}
                                errors={form.errors}
                                onChange={updateField}
                            />
                        </div>
                    ))}

                    <div className="pmc-expense-mobile-actions">
                        <Link href={page.backHref} className="btn btn-light">
                            {t('actions.cancel')}
                        </Link>
                        <button
                            className="btn btn-primary"
                            disabled={form.processing}
                        >
                            {text(page.submitLabel)}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    );
}
