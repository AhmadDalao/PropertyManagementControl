import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { WorkspaceAction } from './types';

export function WorkspaceActionControl({
    action,
}: {
    action: WorkspaceAction;
}) {
    const { text } = useTranslator();
    const className = `pmc-workspace-action is-${action.tone ?? 'secondary'}`;
    const content = (
        <>
            {action.icon ? (
                <i className={`bi ${action.icon}`} aria-hidden="true" />
            ) : null}
            <span>{text(action.label)}</span>
        </>
    );

    if (action.downloads) {
        return (
            <details className="pmc-workspace-action-menu">
                <summary className={className}>{content}</summary>
                <div>
                    {action.downloads.map((download) => (
                        <a
                            href={download.href}
                            key={`${download.href}-${download.label}`}
                        >
                            <i
                                className={`bi ${download.icon ?? 'bi-download'}`}
                                aria-hidden="true"
                            />
                            <span>{text(download.label)}</span>
                        </a>
                    ))}
                </div>
            </details>
        );
    }

    return action.native ? (
        <a href={action.href} className={className}>
            {content}
        </a>
    ) : (
        <Link href={action.href} className={className}>
            {content}
        </Link>
    );
}
