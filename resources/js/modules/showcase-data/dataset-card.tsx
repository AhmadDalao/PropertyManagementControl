import { useTranslator } from '@/lib/i18n';
import { dateTime } from '@/lib/utils';

import { primaryCountKeys, showcaseLabel } from './showcase-labels';
import type { ShowcaseDataset } from './types';

export function DatasetCard({
    dataset,
    busy,
    onRetry,
    onPurge,
}: {
    dataset: ShowcaseDataset;
    busy: boolean;
    onRetry: (dataset: ShowcaseDataset) => void;
    onPurge: (dataset: ShowcaseDataset) => void;
}) {
    const { locale, t } = useTranslator();
    const extraCounts = Object.entries(dataset.counts).filter(
        ([key]) => !primaryCountKeys.some((primaryKey) => primaryKey === key),
    );
    const progressLabel = t('showcase.progress', undefined, {
        generated: dataset.generated_properties,
        target: dataset.target_properties,
    });

    return (
        <article className="pmc-showcase-dataset">
            <header className="pmc-showcase-dataset-header">
                <div>
                    <span className="pmc-showcase-key">{dataset.key}</span>
                    <h3>{dataset.name}</h3>
                    <p>
                        {dataset.initiated_by
                            ? t('showcase.initiated_by', undefined, {
                                  name: dataset.initiated_by,
                              })
                            : t('showcase.system_control')}
                    </p>
                </div>
                <span className={`pmc-showcase-status is-${dataset.status}`}>
                    {t(`status.${dataset.status}`, dataset.status)}
                </span>
            </header>

            <div className="pmc-showcase-progress">
                <div>
                    <strong>{progressLabel}</strong>
                    <span>{dataset.progress_percent}%</span>
                </div>
                <div
                    className="progress"
                    role="progressbar"
                    aria-label={progressLabel}
                    aria-valuenow={dataset.progress_percent}
                    aria-valuemin={0}
                    aria-valuemax={100}
                >
                    <div
                        className="progress-bar"
                        style={{ width: `${dataset.progress_percent}%` }}
                    />
                </div>
            </div>

            <div className="pmc-showcase-counts" role="list">
                {primaryCountKeys.map((key) => (
                    <div key={key} role="listitem">
                        <span>{showcaseLabel(key, t)}</span>
                        <strong>
                            {(dataset.counts[key] ?? 0).toLocaleString(locale)}
                        </strong>
                    </div>
                ))}
            </div>

            {extraCounts.length > 0 ? (
                <details className="pmc-showcase-more-counts">
                    <summary>{t('showcase.more_counts')}</summary>
                    <div>
                        {extraCounts.map(([key, value]) => (
                            <span key={key}>
                                {showcaseLabel(key, t)}
                                <strong>{value.toLocaleString(locale)}</strong>
                            </span>
                        ))}
                    </div>
                </details>
            ) : null}

            {dataset.failure_details ? (
                <pre className="pmc-showcase-failure">
                    {dataset.failure_details}
                </pre>
            ) : null}

            <DatasetCardFooter
                dataset={dataset}
                locale={locale}
                busy={busy}
                onRetry={onRetry}
                onPurge={onPurge}
            />
        </article>
    );
}

function DatasetCardFooter({
    dataset,
    locale,
    busy,
    onRetry,
    onPurge,
}: {
    dataset: ShowcaseDataset;
    locale: string;
    busy: boolean;
    onRetry: (dataset: ShowcaseDataset) => void;
    onPurge: (dataset: ShowcaseDataset) => void;
}) {
    const { t } = useTranslator();
    const date =
        dataset.purged_at ?? dataset.completed_at ?? dataset.started_at;
    const dateLabel = dataset.purged_at
        ? t('showcase.purged_at')
        : dataset.completed_at
          ? t('showcase.completed_at')
          : t('showcase.started_at');

    return (
        <footer className="pmc-showcase-dataset-footer">
            <small>
                {date ? `${dateLabel} ${dateTime(date, locale)}` : dateLabel}
            </small>
            <div>
                {dataset.can_retry ? (
                    <button
                        type="button"
                        className="btn btn-outline-secondary"
                        disabled={busy}
                        onClick={() => onRetry(dataset)}
                    >
                        <i className="bi bi-arrow-repeat" aria-hidden="true" />
                        {t('showcase.retry')}
                    </button>
                ) : null}
                {dataset.can_purge ? (
                    <button
                        type="button"
                        className="btn btn-outline-danger"
                        disabled={busy}
                        onClick={() => onPurge(dataset)}
                    >
                        <i className="bi bi-trash3" aria-hidden="true" />
                        {t('showcase.purge')}
                    </button>
                ) : null}
            </div>
        </footer>
    );
}
