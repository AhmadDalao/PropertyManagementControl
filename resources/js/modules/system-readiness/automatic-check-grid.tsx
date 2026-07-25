import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import { ReadinessStatusBadge } from './readiness-status';
import type { AutomaticReadinessCheck } from './types';

export function AutomaticCheckGrid({
    checks,
}: {
    checks: AutomaticReadinessCheck[];
}) {
    const { t } = useTranslator();

    return (
        <div className="pmc-readiness-check-grid" role="list">
            {checks.map((check) => (
                <article
                    key={check.key}
                    className={`pmc-readiness-check is-${check.status}`}
                    role="listitem"
                >
                    <header>
                        <h3>{check.label}</h3>
                        <ReadinessStatusBadge status={check.status} />
                    </header>
                    <p>{check.description}</p>
                    <div className="pmc-readiness-check-detail">
                        {check.detail}
                    </div>
                    {check.href ? (
                        <Link href={check.href}>
                            {t('readiness.open_fix')}
                            <i
                                className="bi bi-arrow-up-right"
                                aria-hidden="true"
                            />
                        </Link>
                    ) : null}
                </article>
            ))}
        </div>
    );
}
