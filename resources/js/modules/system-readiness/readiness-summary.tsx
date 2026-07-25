import { useTranslator } from '@/lib/i18n';

import type { SystemReadinessPageProps } from './types';

export function ReadinessSummary({
    summary,
    checkedAt,
}: Pick<SystemReadinessPageProps, 'summary' | 'checkedAt'>) {
    const { locale, t } = useTranslator();
    const metrics = [
        {
            key: 'ready',
            label: t('readiness.ready'),
            value: summary.ready,
            icon: 'bi-check2-circle',
        },
        {
            key: 'attention',
            label: t('readiness.attention'),
            value: summary.attention,
            icon: 'bi-exclamation-triangle',
        },
        {
            key: 'blocked',
            label: t('readiness.blocked'),
            value: summary.blocked,
            icon: 'bi-shield-exclamation',
        },
    ];
    const state =
        summary.blocked > 0
            ? t('readiness.launch_blocked')
            : summary.attention > 0
              ? t('readiness.launch_attention')
              : t('readiness.launch_ready');

    return (
        <section className="pmc-readiness-overview">
            <article className="pmc-readiness-decision">
                <span>{t('readiness.current_decision')}</span>
                <strong>{state}</strong>
                <p>{t('readiness.decision_help')}</p>
                <small>
                    {t('readiness.checked_at', undefined, {
                        date: new Intl.DateTimeFormat(locale, {
                            dateStyle: 'medium',
                            timeStyle: 'short',
                        }).format(new Date(checkedAt)),
                    })}
                </small>
            </article>
            <div className="pmc-readiness-summary-grid" role="list">
                {metrics.map((metric) => (
                    <article
                        key={metric.key}
                        className={`is-${metric.key}`}
                        role="listitem"
                    >
                        <i className={`bi ${metric.icon}`} aria-hidden="true" />
                        <div>
                            <strong>
                                {metric.value.toLocaleString(locale)}
                            </strong>
                            <span>{metric.label}</span>
                        </div>
                    </article>
                ))}
            </div>
        </section>
    );
}
