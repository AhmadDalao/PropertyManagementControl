import { useTranslator } from '@/lib/i18n';

import type { DocumentDetailPage } from './types';

export function DocumentDetailMetrics({
    stats,
}: Pick<DocumentDetailPage, 'stats'>) {
    const { t, text } = useTranslator();

    return (
        <section
            className="pmc-document-detail-metrics"
            aria-label={t('documents.record_summary')}
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
