import { ActionLink } from '@/components/resource-cycle/action-link';
import { useTranslator } from '@/lib/i18n';

import type { PortfolioDetailPage } from './types';

export function PortfolioNextStepPanel({
    workflow,
}: Pick<PortfolioDetailPage, 'workflow'>) {
    const { t, text } = useTranslator();
    const actions = workflow.actions ?? [];

    return (
        <section
            className={`pmc-portfolio-next-step is-${workflow.tone ?? 'muted'}`}
            aria-labelledby="portfolio-next-step-title"
        >
            <header>
                <span aria-hidden="true">
                    <i
                        className={`bi ${workflow.icon ?? 'bi-signpost-split'}`}
                    />
                </span>
                <div>
                    <small>{text(workflow.eyebrow)}</small>
                    <h2 id="portfolio-next-step-title">
                        {text(workflow.title)}
                    </h2>
                </div>
                {workflow.status ? <em>{text(workflow.status)}</em> : null}
            </header>
            {workflow.description ? <p>{text(workflow.description)}</p> : null}
            <div>
                {actions.length > 0 ? (
                    actions.map((action) => (
                        <ActionLink
                            action={action}
                            key={`${action.href}-${action.label}`}
                        />
                    ))
                ) : (
                    <small>{t('resource.no_available_actions')}</small>
                )}
            </div>
        </section>
    );
}
