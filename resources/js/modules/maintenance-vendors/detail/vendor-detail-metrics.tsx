import type { DetailItem } from '@/components/resource-cycle';
import { useTranslator } from '@/lib/i18n';

export function VendorDetailMetrics({ stats }: { stats: DetailItem[] }) {
    const { t, text } = useTranslator();

    return (
        <section
            className="pmc-vendor-detail-metrics"
            aria-label={t('maintenance_vendors.record_summary')}
        >
            {stats.map((stat) => (
                <article
                    className={`is-${stat.tone ?? 'muted'}`}
                    key={String(stat.label)}
                >
                    <span>{text(stat.label)}</span>
                    <strong>{stat.value}</strong>
                </article>
            ))}
        </section>
    );
}
