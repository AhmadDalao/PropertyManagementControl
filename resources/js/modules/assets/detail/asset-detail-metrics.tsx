import { useTranslator } from '@/lib/i18n';

import type { AssetDetailPage } from './types';

export function AssetDetailMetrics({ stats }: Pick<AssetDetailPage, 'stats'>) {
    const { t, text } = useTranslator();

    return (
        <section
            className="pmc-asset-detail-metrics"
            aria-label={t('assets.operating_summary')}
        >
            {stats.map((stat) => (
                <article
                    className={`is-${stat.tone ?? 'muted'}`}
                    key={stat.label}
                >
                    <span>{text(stat.label)}</span>
                    <strong>{stat.value ?? '-'}</strong>
                </article>
            ))}
        </section>
    );
}
