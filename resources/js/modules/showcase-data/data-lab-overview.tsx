import { useTranslator } from '@/lib/i18n';

import type { ShowcaseSummary } from './types';

export function DataLabOverview({
    summary,
    canGenerate,
    legacyCandidates,
    busy,
    onGenerate,
}: {
    summary: ShowcaseSummary;
    canGenerate: boolean;
    legacyCandidates: number;
    busy: boolean;
    onGenerate: () => void;
}) {
    const { locale, t } = useTranslator();
    const metrics = [
        {
            icon: 'bi-database',
            label: t('showcase.datasets'),
            value: summary.datasets,
        },
        {
            icon: 'bi-buildings',
            label: t('showcase.live_buildings'),
            value: summary.live_buildings,
        },
        {
            icon: 'bi-hourglass-split',
            label: t('showcase.active_datasets'),
            value: summary.active,
        },
        {
            icon: 'bi-exclamation-circle',
            label: t('showcase.failed_datasets'),
            value: summary.failed,
        },
    ];

    return (
        <section className="pmc-showcase-overview">
            <div className="pmc-showcase-warning">
                <span aria-hidden="true">
                    <i className="bi bi-exclamation-triangle" />
                </span>
                <div>
                    <strong>{t('showcase.warning_title')}</strong>
                    <p>{t('showcase.warning_description')}</p>
                    {legacyCandidates > 0 ? (
                        <small>
                            {t('showcase.legacy_candidates', undefined, {
                                count: legacyCandidates,
                            })}
                        </small>
                    ) : null}
                </div>
                <button
                    type="button"
                    className="btn btn-primary"
                    disabled={!canGenerate || busy}
                    onClick={onGenerate}
                >
                    <i className="bi bi-database-add" />
                    {t('showcase.generate')}
                </button>
            </div>

            <div className="pmc-showcase-summary" role="list">
                {metrics.map((metric) => (
                    <article key={metric.label} role="listitem">
                        <span aria-hidden="true">
                            <i className={`bi ${metric.icon}`} />
                        </span>
                        <div>
                            <small>{metric.label}</small>
                            <strong>
                                {metric.value.toLocaleString(locale)}
                            </strong>
                        </div>
                    </article>
                ))}
            </div>
        </section>
    );
}
