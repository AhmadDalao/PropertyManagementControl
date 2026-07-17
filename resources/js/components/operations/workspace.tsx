import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

export type WorkspaceAction = {
    label: string;
    href: string;
    icon?: string;
    tone?: 'primary' | 'secondary' | 'quiet';
    native?: boolean;
};

export type WorkspaceMetric = {
    label: string;
    value: ReactNode;
    detail?: ReactNode;
    icon: string;
    tone?: 'ink' | 'blue' | 'teal' | 'amber' | 'red';
    href?: string;
};

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
    return (
        <header className="pmc-workspace-header">
            <div className="pmc-workspace-heading">
                <span>{eyebrow}</span>
                <h1>{title}</h1>
                <p>{description}</p>
            </div>

            {actions.length > 0 ? (
                <div className="pmc-workspace-actions">
                    {actions.map((action) => {
                        const content = (
                            <>
                                {action.icon ? (
                                    <i className={`bi ${action.icon}`} />
                                ) : null}
                                <span>{action.label}</span>
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

export function MetricGrid({ metrics }: { metrics: WorkspaceMetric[] }) {
    return (
        <section className="pmc-metric-grid" aria-label="Workspace summary">
            {metrics.map((metric) => {
                const content = (
                    <>
                        <div className="pmc-metric-icon">
                            <i className={`bi ${metric.icon}`} />
                        </div>
                        <span>{metric.label}</span>
                        <strong>{metric.value}</strong>
                        {metric.detail ? <small>{metric.detail}</small> : null}
                    </>
                );
                const className = `pmc-metric-card is-${metric.tone ?? 'ink'}`;

                return metric.href ? (
                    <Link
                        key={metric.label}
                        href={metric.href}
                        className={className}
                    >
                        {content}
                    </Link>
                ) : (
                    <article key={metric.label} className={className}>
                        {content}
                    </article>
                );
            })}
        </section>
    );
}

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
    return (
        <section className={`pmc-workspace-panel ${className}`}>
            <div className="pmc-workspace-panel-head">
                <div>
                    {eyebrow ? <span>{eyebrow}</span> : null}
                    <h2>{title}</h2>
                    {description ? <p>{description}</p> : null}
                </div>
                {action ? (
                    <Link href={action.href}>
                        {action.label}
                        <i className="bi bi-arrow-up-right" />
                    </Link>
                ) : null}
            </div>
            {children}
        </section>
    );
}

export function StatusBadge({
    value,
    tone,
}: {
    value: string;
    tone?: 'success' | 'warning' | 'danger' | 'neutral' | 'blue';
}) {
    return (
        <span className={`pmc-status-badge is-${tone ?? statusTone(value)}`}>
            {humanLabel(value)}
        </span>
    );
}

export function RecordActions({
    showHref,
    editHref,
    children,
}: {
    showHref: string;
    editHref?: string;
    children?: ReactNode;
}) {
    return (
        <div className="pmc-record-actions">
            <Link href={showHref} className="pmc-record-open">
                Open
                <i className="bi bi-arrow-up-right" />
            </Link>
            {editHref ? (
                <Link
                    href={editHref}
                    className="btn btn-outline-secondary btn-sm"
                    aria-label="Edit record"
                >
                    <i className="bi bi-pencil" />
                    <span>Edit</span>
                </Link>
            ) : null}
            {children}
        </div>
    );
}

export function humanLabel(value: string) {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function statusTone(
    value: string,
): 'success' | 'warning' | 'danger' | 'neutral' | 'blue' {
    if (['active', 'posted', 'paid', 'resolved', 'published'].includes(value)) {
        return 'success';
    }

    if (
        ['open', 'pending', 'draft', 'reserved', 'in_progress'].includes(value)
    ) {
        return 'warning';
    }

    if (
        ['blocked', 'suspended', 'overdue', 'cancelled', 'terminated'].includes(
            value,
        )
    ) {
        return 'danger';
    }

    if (['occupied', 'commercial', 'company'].includes(value)) {
        return 'blue';
    }

    return 'neutral';
}
