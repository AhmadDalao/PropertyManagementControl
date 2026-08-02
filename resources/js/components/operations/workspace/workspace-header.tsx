import { useTranslator } from '@/lib/i18n';

import type { WorkspaceAction } from './types';
import { WorkspaceActionControl } from './workspace-action';

export function WorkspaceHeader({
    eyebrow,
    title,
    description,
    actions = [],
}: {
    eyebrow: string;
    title: string;
    description: string;
    actions?: WorkspaceAction[];
}) {
    const { text } = useTranslator();

    return (
        <header className="pmc-workspace-header">
            <div className="pmc-workspace-heading">
                <span>{text(eyebrow)}</span>
                <h1>{text(title)}</h1>
                <p>{text(description)}</p>
            </div>

            {actions.length > 0 ? (
                <div className="pmc-workspace-actions">
                    {actions.map((action) => (
                        <WorkspaceActionControl
                            action={action}
                            key={`${action.href ?? 'menu'}-${action.label}`}
                        />
                    ))}
                </div>
            ) : null}
        </header>
    );
}
