import { useTranslator } from '@/lib/i18n';

const CONTEXT_POINTS = [
    ['bi-shield-check', 'login.role_access'],
    ['bi-translate', 'login.bilingual'],
    ['bi-file-earmark-lock', 'login.private_documents'],
] as const;

export function AuthContextPanel() {
    const { t } = useTranslator();

    return (
        <div className="pmc-auth-copy">
            <div className="pmc-kicker mb-3">{t('login.secure_portal')}</div>
            <h2>{t('login.headline')}</h2>
            <p>{t('login.description')}</p>
            <div className="pmc-auth-points">
                {CONTEXT_POINTS.map(([icon, key]) => (
                    <span key={key}>
                        <i className={`bi ${icon}`} aria-hidden="true" />
                        {t(key)}
                    </span>
                ))}
            </div>
        </div>
    );
}
