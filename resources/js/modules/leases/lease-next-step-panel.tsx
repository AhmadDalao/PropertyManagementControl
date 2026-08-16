import { ActionLink } from '@/components/resource-cycle/action-link';
import type { ResourceWorkflow } from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

export function LeaseNextStepPanel({
    workflow,
}: {
    workflow: ResourceWorkflow;
}) {
    const { t, text } = useTranslator();
    const actions = workflow.actions ?? [];

    return (
        <section
            className={`pmc-lease-next-step is-${workflow.tone ?? 'muted'}`}
            aria-labelledby="pmc-lease-next-step-title"
        >
            <header>
                <span className="pmc-lease-next-step-icon" aria-hidden="true">
                    <i
                        className={`bi ${workflow.icon ?? 'bi-signpost-split'}`}
                    />
                </span>
                <div>
                    <span>{text(workflow.eyebrow)}</span>
                    <h2 id="pmc-lease-next-step-title">
                        {text(workflow.title)}
                    </h2>
                </div>
                {workflow.status ? <em>{text(workflow.status)}</em> : null}
            </header>
            {workflow.description ? <p>{text(workflow.description)}</p> : null}
            <div className="pmc-lease-next-step-actions">
                {actions.length > 0 ? (
                    actions.map((action) => (
                        <ActionLink
                            key={`${action.href}-${action.label}`}
                            action={action}
                        />
                    ))
                ) : (
                    <small>{t('resource.no_available_actions')}</small>
                )}
            </div>
        </section>
    );
}
