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
    formSubmit,
    formCancel,
}: ResourceHeaderProps) {
    const { t, text } = useTranslator();
    const primaryActions = actions.slice(0, 2);
    const overflowActions = actions.slice(2);

    return (
        <section className="pmc-resource-header">
            <div>
                {backHref ? (
                    <nav
                        className="pmc-resource-breadcrumb"
                        aria-label={t('common.breadcrumbs', 'Breadcrumbs')}
                    >
                        <Link href={backHref}>{text(backLabel)}</Link>
                        <i className="bi bi-chevron-right" aria-hidden="true" />
                        <span>{text(title)}</span>
                    </nav>
                ) : (
                    <div className="pmc-kicker">{text(eyebrow)}</div>
                )}
                <h1>{text(title)}</h1>
                {description ? <p>{text(description)}</p> : null}
            </div>
            <div className="pmc-resource-actions">
                {formCancel ? (
                    <Link
                        href={formCancel.href}
                        className="btn btn-light pmc-resource-header-cancel"
                    >
                        {text(formCancel.label)}
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
                {formSubmit ? (
                    <button
                        className="btn btn-primary pmc-resource-header-submit"
                        type="submit"
                        form={formSubmit.form}
                    >
                        <i className="bi bi-check2" aria-hidden="true" />
                        {text(formSubmit.label)}
                    </button>
                ) : null}
            </div>
        </section>
    );
}
