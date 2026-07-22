import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { WorkspaceAction } from './types';

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
                    {actions.map((action) => {
                        const content = (
                            <>
                                {action.icon ? (
                                    <i className={`bi ${action.icon}`} />
                                ) : null}
                                <span>{text(action.label)}</span>
                            </>
                        );
                        const className = `pmc-workspace-action is-${action.tone ?? 'secondary'}`;

                        return action.native ? (
                            <a
                                key={`${action.href}-${action.label}`}
                                href={action.href}
                                className={className}
                            >
                                {content}
                            </a>
                        ) : (
                            <Link
                                key={`${action.href}-${action.label}`}
                                href={action.href}
                                className={className}
                            >
                                {content}
                            </Link>
                        );
                    })}
                </div>
            ) : null}
        </header>
    );
}
