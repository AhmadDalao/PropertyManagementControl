import { useTranslator } from '@/lib/i18n';

import type { WorkOrderDetailPage } from './types';

export function WorkOrderDetailMetrics({
    stats,
}: Pick<WorkOrderDetailPage, 'stats'>) {
    const { t, text } = useTranslator();

    return (
        <section
            className="pmc-work-order-detail-metrics"
            aria-label={t('work_orders.record_summary')}
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
