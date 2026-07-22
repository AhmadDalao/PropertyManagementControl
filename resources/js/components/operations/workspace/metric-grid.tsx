import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { WorkspaceMetric } from './types';

export function MetricGrid({ metrics }: { metrics: WorkspaceMetric[] }) {
    const { text } = useTranslator();

    return (
        <section
            className="pmc-metric-grid"
            aria-label={text('Workspace summary')}
        >
            {metrics.map((metric) => {
                const content = (
                    <>
                        <div className="pmc-metric-icon">
                            <i className={`bi ${metric.icon}`} />
                        </div>
                        <span>{text(metric.label)}</span>
                        <strong>{metric.value}</strong>
                        {metric.detail ? (
                            <small>
                                {typeof metric.detail === 'string'
                                    ? text(metric.detail)
                                    : metric.detail}
                            </small>
                        ) : null}
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
