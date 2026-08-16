import { Link } from '@inertiajs/react';

import type { ResourceProgress } from '@/components/resource-cycle';
import { useTranslator } from '@/lib/i18n';

export function MaintenanceLifecyclePanel({
    progress,
}: {
    progress: ResourceProgress;
}) {
    const { t, text } = useTranslator();

    return (
        <section className="pmc-maintenance-panel pmc-maintenance-lifecycle">
            <header>
                <div>
                    <h2>{text(progress.title)}</h2>
                    {progress.description ? (
                        <p>{text(progress.description)}</p>
                    ) : null}
                </div>
                <strong>{text(progress.summary)}</strong>
            </header>
            <ol>
                {progress.steps.map((step, index) => (
                    <li
                        className={`is-${step.state}`}
                        key={`${step.title}-${index}`}
                    >
                        <span>{index + 1}</span>
                        <div>
                            <strong>{text(step.title)}</strong>
                            <p>{text(step.description)}</p>
                        </div>
                        <em>{t(`status.${step.state}`, step.state)}</em>
                        {step.href && step.actionLabel ? (
                            step.download ? (
                                <a href={step.href}>{text(step.actionLabel)}</a>
                            ) : (
                                <Link href={step.href}>
                                    {text(step.actionLabel)}
                                </Link>
                            )
                        ) : null}
                    </li>
                ))}
            </ol>
        </section>
    );
}
