import { useTranslator } from '@/lib/i18n';

import type { PortfolioDetailPage } from './types';

export function PortfolioDetailMetrics({
    stats,
}: Pick<PortfolioDetailPage, 'stats'>) {
    const { t, text } = useTranslator();

    return (
        <section
            className="pmc-portfolio-detail-metrics"
            aria-label={t('portfolios.account_summary')}
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
