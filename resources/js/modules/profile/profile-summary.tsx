import { humanLabel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';

import type { ProfileRecord } from './types';

export function ProfileSummary({ profile }: { profile: ProfileRecord }) {
    const { locale, t } = useTranslator();
    const role =
        profile.roles
            .map((value) =>
                t(`roles.${value}` as UiTranslationKey, humanLabel(value)),
            )
            .join(' / ') || t('profile.user');
    const facts = [
        {
            label: t('profile.status'),
            value: t(
                `status.${profile.status}` as UiTranslationKey,
                humanLabel(profile.status),
            ),
        },
        { label: t('profile.role'), value: role },
        {
            label: t('profile.language'),
            value:
                profile.preferred_locale === 'ar'
                    ? t('profile.arabic')
                    : t('profile.english'),
        },
        {
            label: t('profile.last_login'),
            value: formatProfileDate(
                profile.last_login_at,
                locale,
                t('profile.not_recorded'),
            ),
        },
    ];

    return (
        <section
            className="pmc-profile-summary"
            aria-label={t('profile.signed_in_account')}
        >
            <div className="pmc-profile-identity">
                <div className="pmc-profile-avatar" aria-hidden="true">
                    {profile.name.slice(0, 1).toUpperCase()}
                </div>
                <div>
                    <span>{t('profile.signed_in_account')}</span>
                    <h2>{profile.name}</h2>
                    <p>{profile.email}</p>
                </div>
            </div>

            <div className="pmc-profile-facts">
                {facts.map((fact) => (
                    <article key={fact.label}>
                        <span>{fact.label}</span>
                        <strong>{fact.value}</strong>
                    </article>
                ))}
            </div>
        </section>
    );
}

function formatProfileDate(
    value: string | null | undefined,
    locale: string,
    fallback: string,
): string {
    if (!value) {
        return fallback;
    }

    return new Intl.DateTimeFormat(locale === 'ar' ? 'ar-SA' : 'en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
