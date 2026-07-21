import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import type { PropertyMapSummary } from './types';

export function PropertyMapMetrics({
    summary,
    totalValue,
}: {
    summary: PropertyMapSummary;
    totalValue: number;
}) {
    const { locale, t } = useTranslator();

    return (
        <div className="pmc-property-map-metrics" role="list">
            <MapMetric
                icon="bi-buildings"
                label={t('map.properties')}
                value={summary.total}
                detail={t('map.properties_detail', undefined, {
                    count: summary.zones.length,
                })}
            />
            <MapMetric
                icon="bi-check2-circle"
                label={t('map.map_ready')}
                value={`${summary.coverage_percent}%`}
                detail={t('map.ready_detail', undefined, {
                    ready: summary.ready,
                    total: summary.total,
                })}
                tone="teal"
            />
            <MapMetric
                icon="bi-geo-alt"
                label={t('map.positioned')}
                value={summary.mapped}
                detail={t('map.positioned_detail', undefined, {
                    total: summary.total,
                })}
            />
            <MapMetric
                icon="bi-cash-stack"
                label={t('map.mapped_value')}
                value={currency(totalValue, locale)}
                detail={t('map.value_detail')}
            />
        </div>
    );
}

function MapMetric({
    icon,
    label,
    value,
    detail,
    tone,
}: {
    icon: string;
    label: string;
    value: string | number;
    detail: string;
    tone?: 'teal';
}) {
    return (
        <article
            className={`pmc-property-map-metric ${tone ? `is-${tone}` : ''}`}
            role="listitem"
        >
            <span>
                <i className={`bi ${icon}`} />
            </span>
            <div>
                <small>{label}</small>
                <strong>{value}</strong>
                <p>{detail}</p>
            </div>
        </article>
    );
}
