import { useTranslator } from '@/lib/i18n';

import type { InfrastructureStatusCheck } from './types';

const icons: Record<InfrastructureStatusCheck['key'], string> = {
    mail: 'bi-envelope-check',
    scheduler: 'bi-clock-history',
    queue: 'bi-database-check',
};

export function InfrastructureStatusGrid({
    checks,
}: {
    checks: InfrastructureStatusCheck[];
}) {
    const { t } = useTranslator();

    return (
        <section
            className="pmc-infrastructure-status-grid"
            aria-label={t('infrastructure_settings.status_title')}
        >
            {checks.map((check) => (
                <article key={check.key} className={`is-${check.status}`}>
                    <span className="pmc-infrastructure-status-icon">
                        <i
                            className={`bi ${icons[check.key]}`}
                            aria-hidden="true"
                        />
                    </span>
                    <div>
                        <span className="pmc-infrastructure-status-label">
                            {check.label}
                        </span>
                        <strong>
                            {t(
                                `infrastructure_settings.status.${check.status}`,
                            )}
                        </strong>
                        <p>{check.detail}</p>
                    </div>
                </article>
            ))}
        </section>
    );
}
