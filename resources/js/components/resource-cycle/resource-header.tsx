import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import { ActionLink } from './action-link';
import type { ResourceHeaderProps } from './types';

export function ResourceHeader({
    eyebrow = 'Workspace',
    title,
    description,
    backHref,
    backLabel = 'Back',
    actions = [],
}: ResourceHeaderProps) {
    const { t, text } = useTranslator();
    const primaryActions = actions.slice(0, 2);
    const overflowActions = actions.slice(2);

    return (
        <section className="pmc-resource-header">
            <div>
                <div className="pmc-kicker mb-2">{text(eyebrow)}</div>
                <h1>{text(title)}</h1>
                {description ? <p>{text(description)}</p> : null}
            </div>
            <div className="pmc-resource-actions">
                {backHref ? (
                    <Link href={backHref} className="btn btn-light">
                        <i className="bi bi-arrow-left me-2" />
                        {text(backLabel)}
                    </Link>
                ) : null}
                {primaryActions.map((action) => (
                    <ActionLink
                        key={`${action.href}-${action.label}`}
                        action={action}
                    />
                ))}
                {overflowActions.length > 0 ? (
                    <details className="pmc-resource-action-menu">
                        <summary>
                            <i className="bi bi-three-dots" />
                            <span>
                                {t('common.more_actions', 'More actions')}
                            </span>
                        </summary>
                        <div>
                            {overflowActions.map((action) => (
                                <ActionLink
                                    key={`${action.href}-${action.label}`}
                                    action={action}
                                />
                            ))}
                        </div>
                    </details>
                ) : null}
            </div>
        </section>
    );
}
