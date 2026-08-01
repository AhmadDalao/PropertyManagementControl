import { useTranslator } from '@/lib/i18n';
import { currency, humanDate, localizedNumber, percent } from '@/lib/utils';

import type {
    ReportComparison as ReportComparisonData,
    ReportComparisonMetric,
} from './types';

export function ReportComparison({
    comparison,
}: {
    comparison: ReportComparisonData;
}) {
    const { locale, t } = useTranslator();

    return (
        <section
            className="pmc-report-comparison"
            aria-labelledby="report-comparison-title"
        >
            <header className="pmc-report-comparison__header">
                <div>
                    <span>{t('reports.comparison_eyebrow')}</span>
                    <h2 id="report-comparison-title">
                        {t('reports.comparison_title')}
                    </h2>
                    <p>{t('reports.comparison_description')}</p>
                </div>
                <div className="pmc-report-comparison__period">
                    <i className="bi bi-calendar3" aria-hidden="true" />
                    <span>
                        {t('reports.compared_with_period', undefined, {
                            from: humanDate(
                                comparison.period.date_from,
                                locale,
                            ),
                            to: humanDate(comparison.period.date_to, locale),
                        })}
                    </span>
                </div>
            </header>

            <div className="pmc-report-comparison__grid">
                {comparison.currencyPositions.map((position) => (
                    <ComparisonCard
                        key={position.currency}
                        title={position.currency}
                        icon="bi-graph-up-arrow"
                        metrics={position.metrics}
                        currencyCode={position.currency}
                    />
                ))}
                <ComparisonCard
                    title={t('reports.maintenance_activity')}
                    icon="bi-tools"
                    metrics={comparison.serviceMetrics}
                />
            </div>
        </section>
    );
}

function ComparisonCard({
    title,
    icon,
    metrics,
    currencyCode,
}: {
    title: string;
    icon: string;
    metrics: ReportComparisonMetric[];
    currencyCode?: string;
}) {
    const { locale, t } = useTranslator();
    const formatValue = (metric: ReportComparisonMetric): string => {
        if (metric.format === 'money') {
            return currency(metric.current, locale, currencyCode);
        }

        if (metric.format === 'percent') {
            return percent(metric.current, locale);
        }

        return localizedNumber(metric.current, locale);
    };
    const formatPrevious = (metric: ReportComparisonMetric): string => {
        if (metric.format === 'money') {
            return currency(metric.previous, locale, currencyCode);
        }

        if (metric.format === 'percent') {
            return percent(metric.previous, locale);
        }

        return localizedNumber(metric.previous, locale);
    };

    return (
        <article className="pmc-report-comparison-card">
            <header>
                <span className="pmc-report-comparison-card__icon">
                    <i className={`bi ${icon}`} aria-hidden="true" />
                </span>
                <h3>{title}</h3>
            </header>
            <dl>
                {metrics.map((metric) => (
                    <div key={metric.key}>
                        <dt>{t(`reports.${metric.key}`)}</dt>
                        <dd>
                            <strong>{formatValue(metric)}</strong>
                            <span>
                                {t('reports.previous_value', undefined, {
                                    value: formatPrevious(metric),
                                })}
                            </span>
                        </dd>
                        <ChangeBadge metric={metric} />
                    </div>
                ))}
            </dl>
        </article>
    );
}

function ChangeBadge({ metric }: { metric: ReportComparisonMetric }) {
    const { locale, t } = useTranslator();
    const value =
        metric.change === null
            ? t('reports.change_new_activity')
            : metric.change === 0
              ? t('reports.change_no_change')
              : t(
                    metric.changeKind === 'points'
                        ? 'reports.change_points'
                        : 'reports.change_percent',
                    undefined,
                    {
                        value: localizedNumber(Math.abs(metric.change), locale),
                    },
                );

    return (
        <span
            className={`pmc-report-comparison-card__change is-${metric.trend}`}
        >
            {metric.trend === 'up' ? (
                <i className="bi bi-arrow-up" aria-hidden="true" />
            ) : null}
            {metric.trend === 'down' ? (
                <i className="bi bi-arrow-down" aria-hidden="true" />
            ) : null}
            {value}
        </span>
    );
}
