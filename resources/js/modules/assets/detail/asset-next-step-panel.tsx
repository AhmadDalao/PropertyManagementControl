import { ActionLink } from '@/components/resource-cycle/action-link';
import { useTranslator } from '@/lib/i18n';

import type { AssetDetailPage } from './types';

export function AssetNextStepPanel({
    workflow,
}: Pick<AssetDetailPage, 'workflow'>) {
    const { t, text } = useTranslator();
    const actions = workflow.actions ?? [];

    return (
        <section
            className={`pmc-asset-next-step is-${workflow.tone ?? 'muted'}`}
            aria-labelledby="asset-next-step-title"
        >
            <header>
                <span aria-hidden="true">
                    <i
                        className={`bi ${workflow.icon ?? 'bi-signpost-split'}`}
                    />
                </span>
                <div>
                    <small>{text(workflow.eyebrow)}</small>
                    <h2 id="asset-next-step-title">{text(workflow.title)}</h2>
                </div>
                {workflow.status ? <em>{text(workflow.status)}</em> : null}
            </header>
            {workflow.description ? <p>{text(workflow.description)}</p> : null}
            <div>
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
