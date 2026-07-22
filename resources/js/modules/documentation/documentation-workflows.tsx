import { Link } from '@inertiajs/react';

import { WorkspacePanel } from '@/components/operations/workspace';
import { useTranslator } from '@/lib/i18n';

import type { WorkflowTrack } from './types';

export function DocumentationWorkflows({
    workflows,
}: {
    workflows: WorkflowTrack[];
}) {
    const { t } = useTranslator();

    if (workflows.length === 0) {
        return null;
    }

    return (
        <WorkspacePanel
            eyebrow={t('docs.operating_cycles')}
            title={t('docs.run_in_order')}
            description={t('docs.choose_workflow')}
        >
            <div className="pmc-doc-workflow-grid">
                {workflows.map((workflow) => (
                    <article
                        key={workflow.key}
                        className="pmc-doc-workflow-card"
                    >
                        <header>
                            <i className={`bi ${workflow.icon}`} />
                            <div>
                                <span>{workflow.audience}</span>
                                <strong>{workflow.title}</strong>
                                <small>{workflow.summary}</small>
                            </div>
                        </header>
                        <ol>
                            {workflow.steps.slice(0, 6).map((step) => (
                                <li key={step.label}>{step.label}</li>
                            ))}
                        </ol>
                        <footer>
                            <span>{workflow.outcome}</span>
                            <Link
                                href={workflow.route}
                                className="btn btn-primary btn-sm"
                            >
                                {t('docs.start')}
                            </Link>
                        </footer>
                    </article>
                ))}
            </div>
        </WorkspacePanel>
    );
}
