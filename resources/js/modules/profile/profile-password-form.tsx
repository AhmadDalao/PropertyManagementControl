import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { describedBy, ProfileField } from './profile-field';

export function ProfilePasswordForm({
    forcePasswordReset,
}: {
    forcePasswordReset: boolean;
}) {
    const { t } = useTranslator();
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const currentPasswordHelp = forcePasswordReset
        ? t('profile.current_password_optional')
        : undefined;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.put('/profile/password', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    return (
        <section className="pmc-profile-form-card">
            <header>
                <span className="pmc-profile-card-icon is-teal">
                    <i className="bi bi-shield-lock" />
                </span>
                <div>
                    <span>{t('profile.security')}</span>
                    <h2>{t('profile.change_password')}</h2>
                    <p>{t('profile.security_description')}</p>
                </div>
            </header>

            <form onSubmit={submit}>
                <ProfileField
                    id="current-password"
                    label={t('profile.current_password')}
                    help={currentPasswordHelp}
                    error={form.errors.current_password}
                >
                    <input
                        id="current-password"
                        name="current_password"
                        type="password"
                        className="form-control"
                        autoComplete="current-password"
                        required={!forcePasswordReset}
                        value={form.data.current_password}
                        aria-invalid={Boolean(form.errors.current_password)}
                        aria-describedby={describedBy(
                            'current-password',
                            form.errors.current_password,
                            currentPasswordHelp,
                        )}
                        onChange={(event) =>
                            form.setData(
                                'current_password',
                                event.currentTarget.value,
                            )
                        }
                    />
                </ProfileField>

                <ProfileField
                    id="new-password"
                    label={t('profile.new_password')}
                    help={t('profile.password_notice')}
                    error={form.errors.password}
                >
                    <input
                        id="new-password"
                        name="password"
                        type="password"
                        className="form-control"
                        autoComplete="new-password"
                        minLength={8}
                        required
                        value={form.data.password}
                        aria-invalid={Boolean(form.errors.password)}
                        aria-describedby={describedBy(
                            'new-password',
                            form.errors.password,
                            t('profile.password_notice'),
                        )}
                        onChange={(event) =>
                            form.setData('password', event.currentTarget.value)
                        }
                    />
                </ProfileField>

                <ProfileField
                    id="password-confirmation"
                    label={t('profile.confirm_password')}
                    error={form.errors.password_confirmation}
                >
                    <input
                        id="password-confirmation"
                        name="password_confirmation"
                        type="password"
                        className="form-control"
                        autoComplete="new-password"
                        minLength={8}
                        required
                        value={form.data.password_confirmation}
                        aria-invalid={Boolean(
                            form.errors.password_confirmation,
                        )}
                        aria-describedby={describedBy(
                            'password-confirmation',
                            form.errors.password_confirmation,
                        )}
                        onChange={(event) =>
                            form.setData(
                                'password_confirmation',
                                event.currentTarget.value,
                            )
                        }
                    />
                </ProfileField>

                <button
                    type="submit"
                    className="btn btn-warning pmc-profile-form-action"
                    disabled={form.processing}
                >
                    <i className="bi bi-key" />
                    {form.processing
                        ? t('actions.working')
                        : t('profile.update_password')}
                </button>
            </form>
        </section>
    );
}
