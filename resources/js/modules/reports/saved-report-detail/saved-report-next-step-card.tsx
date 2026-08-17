import type { ResourceWorkflow } from '@/components/resource-cycle';
import { ActionLink } from '@/components/resource-cycle/action-link';
import { useTranslator } from '@/lib/i18n';

export function SavedReportNextStepCard({
    workflow,
}: {
    workflow: ResourceWorkflow;
}) {
    const { text } = useTranslator();

    return (
        <article
            className={`pmc-saved-report-next-step is-${workflow.tone ?? 'muted'}`}
        >
            <header>
                <span aria-hidden="true">
                    <i
                        className={`bi ${workflow.icon ?? 'bi-bar-chart-line'}`}
                    />
                </span>
                <div>
                    <small>{text(workflow.eyebrow)}</small>
                    {workflow.status ? <em>{text(workflow.status)}</em> : null}
                </div>
            </header>
            <div>
                <h2>{text(workflow.title)}</h2>
                {workflow.description ? (
                    <p>{text(workflow.description)}</p>
                ) : null}
            </div>
            <footer>
                {(workflow.actions ?? []).map((action) => (
                    <ActionLink
                        action={action}
                        key={`${action.href}-${action.label}`}
                    />
                ))}
            </footer>
        </article>
    );
}
