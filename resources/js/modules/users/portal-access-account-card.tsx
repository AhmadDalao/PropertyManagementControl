import { useTranslator } from '@/lib/i18n';

import type { PortalAccessAccount } from './portal-access-types';

export function PortalAccessAccountCard({
    account,
}: {
    account: PortalAccessAccount;
}) {
    const { t } = useTranslator();

    const details = [
        [t('users.email'), account.email],
        [t('users.role'), account.role],
        [t('users.portfolio'), account.portfolio],
        [
            t('users.preferred_language'),
            t(
                account.preferred_locale === 'ar'
                    ? 'users.arabic'
                    : 'users.english',
            ),
        ],
    ];

    return (
        <article className="pmc-card pmc-portal-account-card">
            <header>
                <div className="pmc-portal-account-avatar" aria-hidden="true">
                    {account.name.slice(0, 1).toUpperCase()}
                </div>
                <div>
                    <span>{t('users.portal_access_account_summary')}</span>
                    <h2>{account.name}</h2>
                    <span
                        className={`pmc-status pmc-status-${account.status === 'active' ? 'active' : 'suspended'}`}
                    >
                        {account.status_label}
                    </span>
                </div>
            </header>
            <dl>
                {details.map(([label, value]) => (
                    <div key={label}>
                        <dt>{label}</dt>
                        <dd
                            dir={label === t('users.email') ? 'ltr' : undefined}
                        >
                            {value}
                        </dd>
                    </div>
                ))}
            </dl>
            <div className="pmc-portal-password-state">
                <i
                    className={`bi ${account.password_change_required ? 'bi-key' : 'bi-shield-check'}`}
                    aria-hidden="true"
                />
                <span>
                    {t(
                        account.password_change_required
                            ? 'users.password_reset_required'
                            : 'users.password_confirmed',
                    )}
                </span>
            </div>
        </article>
    );
}
