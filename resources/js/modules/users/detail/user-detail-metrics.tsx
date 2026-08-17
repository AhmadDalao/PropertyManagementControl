import { useTranslator } from '@/lib/i18n';

import type { UserDetailPage } from './types';

export function UserDetailMetrics({ stats }: Pick<UserDetailPage, 'stats'>) {
    const { t, text } = useTranslator();

    return (
        <section
            className="pmc-user-detail-metrics"
            aria-label={t('users.account_summary')}
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
