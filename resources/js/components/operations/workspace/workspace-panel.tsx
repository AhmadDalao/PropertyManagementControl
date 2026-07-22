import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { WorkspaceAction } from './types';

export function WorkspacePanel({
    eyebrow,
    title,
    description,
    action,
    children,
    className = '',
}: {
    eyebrow?: string;
    title: string;
    description?: string;
    action?: WorkspaceAction;
    children: ReactNode;
    className?: string;
}) {
    const { text } = useTranslator();

    return (
        <section className={`pmc-workspace-panel ${className}`}>
            <div className="pmc-workspace-panel-head">
                <div>
                    {eyebrow ? <span>{text(eyebrow)}</span> : null}
                    <h2>{text(title)}</h2>
                    {description ? <p>{text(description)}</p> : null}
                </div>
                {action ? (
                    <Link href={action.href}>
                        {text(action.label)}
                        <i className="bi bi-arrow-up-right" />
                    </Link>
                ) : null}
            </div>
            {children}
        </section>
    );
}
