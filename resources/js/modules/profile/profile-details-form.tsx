import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { describedBy, ProfileField } from './profile-field';
import type { ProfileRecord } from './types';

export function ProfileDetailsForm({ profile }: { profile: ProfileRecord }) {
    const { t } = useTranslator();
    const form = useForm({
        name: profile.name,
        phone: profile.phone ?? '',
        preferred_locale: profile.preferred_locale,
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.put('/profile', { preserveScroll: true });
    }

    return (
        <section className="pmc-profile-form-card">
            <header>
                <span className="pmc-profile-card-icon is-amber">
                    <i className="bi bi-person-lines-fill" />
                </span>
                <div>
                    <span>{t('profile.details')}</span>
                    <h2>{t('profile.details_title')}</h2>
                    <p>{t('profile.details_description')}</p>
                </div>
            </header>

            <form onSubmit={submit}>
                <ProfileField
                    id="profile-name"
                    label={t('profile.name')}
                    error={form.errors.name}
                >
                    <input
                        id="profile-name"
                        name="name"
                        className="form-control"
                        autoComplete="name"
                        required
                        value={form.data.name}
                        aria-invalid={Boolean(form.errors.name)}
                        aria-describedby={describedBy(
                            'profile-name',
                            form.errors.name,
                        )}
                        onChange={(event) =>
                            form.setData('name', event.currentTarget.value)
                        }
                    />
                </ProfileField>

                <ProfileField
                    id="profile-email"
                    label={t('profile.email')}
                    help={t('profile.email_notice')}
                >
                    <input
                        id="profile-email"
                        name="email"
                        type="email"
                        className="form-control"
                        autoComplete="username"
                        value={profile.email}
                        aria-describedby="profile-email-help"
                        disabled
                    />
                </ProfileField>

                <ProfileField
                    id="profile-phone"
                    label={t('profile.phone')}
                    error={form.errors.phone}
                >
                    <input
                        id="profile-phone"
                        name="phone"
                        type="tel"
                        className="form-control"
                        autoComplete="tel"
                        value={form.data.phone}
                        aria-invalid={Boolean(form.errors.phone)}
                        aria-describedby={describedBy(
                            'profile-phone',
                            form.errors.phone,
                        )}
                        onChange={(event) =>
                            form.setData('phone', event.currentTarget.value)
                        }
                    />
                </ProfileField>

                <ProfileField
                    id="profile-locale"
                    label={t('profile.portal_language')}
                    error={form.errors.preferred_locale}
                >
                    <select
                        id="profile-locale"
                        name="preferred_locale"
                        className="form-select"
                        value={form.data.preferred_locale}
                        aria-invalid={Boolean(form.errors.preferred_locale)}
                        aria-describedby={describedBy(
                            'profile-locale',
                            form.errors.preferred_locale,
                        )}
                        onChange={(event) =>
                            form.setData(
                                'preferred_locale',
                                event.currentTarget.value as 'en' | 'ar',
                            )
                        }
                    >
                        <option value="en">{t('profile.english')}</option>
                        <option value="ar">{t('profile.arabic')}</option>
                    </select>
                </ProfileField>

                <button
                    type="submit"
                    className="btn btn-primary pmc-profile-form-action"
                    disabled={form.processing}
                >
                    <i className="bi bi-check2" />
                    {form.processing
                        ? t('actions.working')
                        : t('profile.update_profile')}
                </button>
            </form>
        </section>
    );
}
