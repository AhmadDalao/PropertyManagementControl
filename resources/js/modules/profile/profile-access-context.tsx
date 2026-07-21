import { humanLabel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';

import type { ProfileRecord } from './types';

export function ProfileAccessContext({ profile }: { profile: ProfileRecord }) {
    const { locale, t } = useTranslator();
    const portfolio = profile.portfolio;
    const tenant = profile.tenant_profile;
    const cards = [
        {
            icon: 'bi-buildings',
            title: t('profile.portfolio'),
            value: portfolio
                ? locale === 'ar'
                    ? portfolio.name_ar || portfolio.name_en
                    : portfolio.name_en || portfolio.name_ar
                : t('profile.global_account'),
            detail: portfolio
                ? `${portfolio.code} · ${t(
                      `status.${portfolio.status}` as UiTranslationKey,
                      humanLabel(portfolio.status),
                  )}`
                : t('profile.global_account_description'),
        },
        {
            icon: 'bi-person-badge',
            title: t('profile.tenant_profile'),
            value: tenant
                ? t(
                      `tenants.${tenant.profile_type}` as UiTranslationKey,
                      humanLabel(tenant.profile_type),
                  )
                : t('profile.no_tenant_profile'),
            detail: tenant
                ? t(
                      `status.${tenant.status}` as UiTranslationKey,
                      humanLabel(tenant.status),
                  )
                : t('profile.no_tenant_profile_description'),
        },
        {
            icon: 'bi-shield-check',
            title: t('profile.password_state'),
            value: profile.force_password_reset
                ? t('profile.temporary')
                : t('profile.confirmed'),
            detail: profile.force_password_reset
                ? t('profile.change_before_continuing')
                : t('profile.no_reset_required'),
        },
    ];

    return (
        <section className="pmc-profile-context">
            <header>
                <span>{t('profile.access')}</span>
                <h2>{t('profile.access_context')}</h2>
                <p>{t('profile.access_context_description')}</p>
            </header>
            <div className="pmc-profile-context-grid">
                {cards.map((card) => (
                    <article key={card.title}>
                        <i className={`bi ${card.icon}`} />
                        <div>
                            <span>{card.title}</span>
                            <strong>{card.value}</strong>
                            <small>{card.detail}</small>
                        </div>
                    </article>
                ))}
            </div>
        </section>
    );
}
