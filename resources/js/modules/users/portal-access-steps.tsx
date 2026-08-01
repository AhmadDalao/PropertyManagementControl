import { useTranslator } from '@/lib/i18n';

export function PortalAccessSteps() {
    const { t } = useTranslator();
    const steps = [
        ['bi-link-45deg', 'users.portal_access_step_generate'],
        ['bi-send', 'users.portal_access_step_share'],
        ['bi-shield-check', 'users.portal_access_step_complete'],
    ] as const;

    return (
        <section className="pmc-card pmc-portal-access-steps">
            <header>
                <span>{t('users.portal_access_process')}</span>
                <h2>{t('users.portal_access_process_title')}</h2>
                <p>{t('users.portal_access_process_help')}</p>
            </header>
            <ol>
                {steps.map(([icon, key], index) => (
                    <li key={key}>
                        <span className="pmc-portal-step-number">
                            {index + 1}
                        </span>
                        <i className={`bi ${icon}`} aria-hidden="true" />
                        <div>
                            <strong>{t(`${key}_title`)}</strong>
                            <p>{t(`${key}_help`)}</p>
                        </div>
                    </li>
                ))}
            </ol>
        </section>
    );
}
