import { useTranslator } from '@/lib/i18n';

import type { ExpenseDetailPage } from './types';

export function ExpenseDetailMetrics({
    stats,
}: Pick<ExpenseDetailPage, 'stats'>) {
    const { t, text } = useTranslator();

    return (
        <section
            className="pmc-expense-detail-metrics"
            aria-label={t('expenses.record_summary')}
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
