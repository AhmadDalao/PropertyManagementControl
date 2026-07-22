import { useTranslator } from '@/lib/i18n';

import { ActionLink } from './action-link';
import type { ResourceWorkflow } from './types';

export function WorkflowActionPanel({
    workflow,
}: {
    workflow: ResourceWorkflow;
}) {
    const { t, text } = useTranslator();
    const actions = workflow.actions ?? [];

    return (
        <section
            className={`pmc-resource-workflow pmc-resource-workflow-${workflow.tone ?? 'muted'}`}
            aria-labelledby="pmc-resource-workflow-title"
        >
            <div className="pmc-resource-workflow-icon" aria-hidden="true">
                <i className={`bi ${workflow.icon ?? 'bi-signpost-split'}`} />
            </div>
            <div className="pmc-resource-workflow-copy">
                <span>{text(workflow.eyebrow)}</span>
                <div>
                    <h2 id="pmc-resource-workflow-title">
                        {text(workflow.title)}
                    </h2>
                    {workflow.status ? <em>{text(workflow.status)}</em> : null}
                </div>
                {workflow.description ? (
                    <p>{text(workflow.description)}</p>
                ) : null}
            </div>
            <div className="pmc-resource-workflow-actions">
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
