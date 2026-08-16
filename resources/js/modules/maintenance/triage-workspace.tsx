import { Link } from '@inertiajs/react';

import { ResourceHeader } from '@/components/resource-cycle';
import type {
    ResourceAction,
    ResourceField,
} from '@/components/resource-cycle';
import { useTranslator } from '@/lib/i18n';

import { MaintenanceActionPanel } from './maintenance-action-panel';
import { MaintenanceContextCard } from './maintenance-context-card';
import { MaintenanceFormSection } from './maintenance-form-section';
import { MaintenanceLifecyclePanel } from './maintenance-lifecycle-panel';
import type { MaintenanceTriagePageProps } from './types';
import { useMaintenanceForm } from './use-maintenance-form';

export function MaintenanceTriageWorkspace({
    formPage,
    detailPage,
}: Pick<MaintenanceTriagePageProps, 'formPage' | 'detailPage'>) {
    const { form, submit, updateField } = useMaintenanceForm(formPage);
    const { t, text } = useTranslator();
    const formId = 'pmc-maintenance-triage-form';
    const errors = Object.values(form.errors).filter(Boolean);
    const sections = triageSections(formPage.fields, t);
    const actions = availableActions(
        detailPage.header.actions ?? [],
        detailPage.workflow.actions ?? [],
    );

    return (
        <div className="pmc-maintenance-form-page">
            <ResourceHeader
                title={formPage.title}
                description={formPage.description}
                backHref={formPage.backHref}
                backLabel={formPage.backLabel}
                formCancel={{
                    href: formPage.backHref,
                    label: t('maintenance.open_detail'),
                }}
                formSubmit={{ form: formId, label: formPage.submitLabel }}
            />
            <section className="pmc-maintenance-triage-workspace">
                <form id={formId} onSubmit={submit} noValidate>
                    <MaintenanceContextCard items={detailPage.requestContext} />
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
                    {sections.map((section) => (
                        <MaintenanceFormSection
                            key={section.title}
                            title={section.title}
                            description={section.description}
                            fields={section.fields}
                            values={form.data}
                            errors={form.errors}
                            onChange={updateField}
                        />
                    ))}
                    <div className="pmc-maintenance-mobile-actions">
                        <Link
                            href={formPage.backHref}
                            className="btn btn-light"
                        >
                            {t('maintenance.open_detail')}
                        </Link>
                        <button
                            className="btn btn-primary"
                            disabled={form.processing}
                        >
                            {text(formPage.submitLabel)}
                        </button>
                    </div>
                </form>
                <aside>
                    <MaintenanceLifecyclePanel progress={detailPage.progress} />
                    <MaintenanceActionPanel actions={actions} />
                </aside>
            </section>
        </div>
    );
}

function triageSections(
    fields: ResourceField[],
    t: ReturnType<typeof useTranslator>['t'],
) {
    const groups = [
        {
            title: t('maintenance.triage_section'),
            description: t('maintenance.triage_section_help'),
            names: ['assigned_to_user_id', 'priority', 'status'],
        },
        {
            title: t('maintenance.communication_section'),
            description: t('maintenance.communication_section_help'),
            names: ['comment', 'is_public_comment'],
        },
        {
            title: t('maintenance.resolution_section'),
            description: t('maintenance.resolution_section_help'),
            names: ['resolution_summary', 'internal_notes'],
        },
    ];

    return groups
        .map((group) => ({
            ...group,
            fields: fields.filter((field) => group.names.includes(field.name)),
        }))
        .filter((group) => group.fields.length > 0);
}

function availableActions(
    headerActions: ResourceAction[],
    workflowActions: ResourceAction[],
): ResourceAction[] {
    const byHref = new Map<string, ResourceAction>();

    [...workflowActions, ...headerActions].forEach((action) => {
        if (!/\/maintenance-requests\/\d+\/edit$/.test(action.href)) {
            byHref.set(action.href, action);
        }
    });

    return Array.from(byHref.values());
}
