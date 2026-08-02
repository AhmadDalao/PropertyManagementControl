import { Link } from '@inertiajs/react';
import { useId, useState } from 'react';

import type { ResourceProgress } from './types';

export function ResourceProgressPanel({
    progress,
}: {
    progress: ResourceProgress;
}) {
    const percent =
        progress.total > 0
            ? Math.round((progress.completed / progress.total) * 100)
            : 0;
    const complete = progress.total > 0 && progress.completed >= progress.total;
    const collapsible = Boolean(
        progress.collapseWhenComplete &&
        complete &&
        progress.expandLabel &&
        progress.collapseLabel,
    );
    const progressIdentity = `${progress.title}:${progress.completed}:${progress.total}`;
    const [expandedProgress, setExpandedProgress] = useState<string | null>(
        null,
    );
    const expanded = !collapsible || expandedProgress === progressIdentity;
    const detailsId = useId();
    const titleId = useId();

    return (
        <section
            className={`pmc-resource-progress ${
                collapsible && !expanded ? 'is-collapsed' : ''
            }`}
            aria-labelledby={titleId}
            data-progress-complete={complete ? 'true' : 'false'}
        >
            <header>
                <div>
                    <span>{progress.eyebrow}</span>
                    <h2 id={titleId}>{progress.title}</h2>
                    {progress.description ? (
                        <p>{progress.description}</p>
                    ) : null}
                </div>
                <div className="pmc-resource-progress-actions">
                    <strong>{progress.summary}</strong>
                    {collapsible ? (
                        <button
                            type="button"
                            aria-controls={detailsId}
                            aria-expanded={expanded}
                            data-resource-progress-toggle
                            onClick={() =>
                                setExpandedProgress((current) =>
                                    current === progressIdentity
                                        ? null
                                        : progressIdentity,
                                )
                            }
                        >
                            {expanded
                                ? progress.collapseLabel
                                : progress.expandLabel}
                            <i
                                className={`bi ${
                                    expanded
                                        ? 'bi-chevron-up'
                                        : 'bi-chevron-down'
                                }`}
                                aria-hidden="true"
                            />
                        </button>
                    ) : null}
                </div>
            </header>

            <div
                className="pmc-resource-progress-track"
                role="progressbar"
                aria-label={progress.title}
                aria-valuemin={0}
                aria-valuemax={progress.total}
                aria-valuenow={progress.completed}
            >
                <span style={{ width: `${percent}%` }} />
            </div>

            <div
                id={detailsId}
                className="pmc-resource-progress-details"
                hidden={collapsible && !expanded}
            >
                <ol>
                    {progress.steps.map((step, index) => (
                        <li
                            key={`${step.title}-${index}`}
                            className={`is-${step.state}`}
                        >
                            <span className="pmc-resource-progress-marker">
                                <i
                                    className={`bi ${
                                        step.state === 'complete'
                                            ? 'bi-check2'
                                            : (step.icon ??
                                              'bi-hourglass-split')
                                    }`}
                                />
                            </span>
                            <div>
                                <strong>{step.title}</strong>
                                <p>{step.description}</p>
                                {step.href && step.actionLabel ? (
                                    step.download ? (
                                        <a href={step.href}>
                                            {step.actionLabel}
                                            <i className="bi bi-arrow-right" />
                                        </a>
                                    ) : (
                                        <Link href={step.href}>
                                            {step.actionLabel}
                                            <i className="bi bi-arrow-right" />
                                        </Link>
                                    )
                                ) : null}
                            </div>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}
