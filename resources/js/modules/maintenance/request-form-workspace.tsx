import { Link } from '@inertiajs/react';

import { ResourceHeader } from '@/components/resource-cycle';
import type { ResourceFormShellProps } from '@/components/resource-cycle';
import {
    groupResourceFields,
    sectionId,
} from '@/components/resource-cycle/resource-form-helpers';
import { useTranslator } from '@/lib/i18n';

import { MaintenanceFormGuide } from './maintenance-form-guide';
import { MaintenanceFormSection } from './maintenance-form-section';
import { useMaintenanceForm } from './use-maintenance-form';

export function MaintenanceRequestFormWorkspace({
    page,
}: {
    page: ResourceFormShellProps;
}) {
    const { form, submit, updateField } = useMaintenanceForm(page);
    const { t, text } = useTranslator();
    const groups = groupResourceFields(page.fields);
    const formId = 'pmc-maintenance-request-form';
    const errors = Object.values(form.errors).filter(Boolean);

    return (
        <div className="pmc-maintenance-form-page">
            <ResourceHeader
                eyebrow={t('maintenance.request_form')}
                title={page.title}
                description={page.description}
                backHref={page.backHref}
                backLabel={page.backLabel}
                actions={page.headerActions}
                formCancel={{ href: page.backHref, label: t('actions.cancel') }}
                formSubmit={{ form: formId, label: page.submitLabel }}
            />
            <section className="pmc-maintenance-form-layout">
                <form id={formId} onSubmit={submit} noValidate>
                    {errors.length > 0 ? (
                        <div className="pmc-form-error-summary" role="alert">
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
                            <MaintenanceFormSection
                                title={group.title}
                                description={group.description}
                                fields={group.fields}
                                values={form.data}
                                errors={form.errors}
                                onChange={updateField}
                            />
                        </div>
                    ))}
                    <div className="pmc-maintenance-mobile-actions">
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
                <MaintenanceFormGuide fields={page.fields} values={form.data} />
            </section>
        </div>
    );
}
