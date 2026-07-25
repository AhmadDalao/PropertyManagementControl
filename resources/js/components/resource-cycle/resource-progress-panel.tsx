import { Link } from '@inertiajs/react';

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

    return (
        <section
            className="pmc-resource-progress"
            aria-labelledby="pmc-resource-progress-title"
        >
            <header>
                <div>
                    <span>{progress.eyebrow}</span>
                    <h2 id="pmc-resource-progress-title">{progress.title}</h2>
                    {progress.description ? (
                        <p>{progress.description}</p>
                    ) : null}
                </div>
                <strong>{progress.summary}</strong>
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
                                        : (step.icon ?? 'bi-hourglass-split')
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
        </section>
    );
}
