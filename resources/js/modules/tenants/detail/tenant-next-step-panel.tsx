import { ActionLink } from '@/components/resource-cycle/action-link';
import { useTranslator } from '@/lib/i18n';

import type { TenantDetailPage } from './types';

export function TenantNextStepPanel({
    workflow,
}: Pick<TenantDetailPage, 'workflow'>) {
    const { text } = useTranslator();

    return (
        <section
            className={`pmc-tenant-next-step is-${workflow.tone ?? 'muted'}`}
            aria-labelledby="tenant-next-step-title"
        >
            <header>
                <span aria-hidden="true">
                    <i
                        className={`bi ${workflow.icon ?? 'bi-signpost-split'}`}
                    />
                </span>
                <div>
                    <small>{text(workflow.eyebrow)}</small>
                    <h2 id="tenant-next-step-title">{text(workflow.title)}</h2>
                </div>
                {workflow.status ? <em>{text(workflow.status)}</em> : null}
            </header>
            {workflow.description ? <p>{text(workflow.description)}</p> : null}
            <div>
                {(workflow.actions ?? []).map((action) => (
                    <ActionLink
                        action={action}
                        key={`${action.href}-${action.label}`}
                    />
                ))}
            </div>
        </section>
    );
}
