import { useTranslator } from '@/lib/i18n';

import type { TenantDetailPage } from './types';

export function TenantDetailMetrics({
    stats,
}: Pick<TenantDetailPage, 'stats'>) {
    const { t, text } = useTranslator();

    return (
        <section
            className="pmc-tenant-detail-metrics"
            aria-label={t('tenants.account_summary')}
            data-testid="tenant-detail-metrics"
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
