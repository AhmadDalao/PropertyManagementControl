import type { InertiaFormProps } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { InfrastructureSettingsInput } from './types';

export function SmtpSettingsFields({
    form,
    passwordConfigured,
}: {
    form: InertiaFormProps<InfrastructureSettingsInput>;
    passwordConfigured: boolean;
}) {
    const { t } = useTranslator();

    return (
        <section className="pmc-infrastructure-panel">
            <header>
                <span className="pmc-infrastructure-panel-icon">
                    <i className="bi bi-envelope-check" aria-hidden="true" />
                </span>
                <div>
                    <span>{t('infrastructure_settings.mail_eyebrow')}</span>
                    <h2>{t('infrastructure_settings.mail_title')}</h2>
                    <p>{t('infrastructure_settings.mail_description')}</p>
                </div>
            </header>

            <label className="pmc-infrastructure-toggle">
                <input
                    type="checkbox"
                    checked={form.data.mail_enabled}
                    onChange={(event) =>
                        form.setData('mail_enabled', event.target.checked)
                    }
                />
                <span>
                    <strong>{t('infrastructure_settings.enable_mail')}</strong>
                    <small>
                        {t('infrastructure_settings.enable_mail_help')}
                    </small>
                </span>
            </label>

            <div className="pmc-infrastructure-fields">
                <label>
                    <span>{t('infrastructure_settings.mail_host')}</span>
                    <input
                        className="form-control"
                        value={form.data.mail_host}
                        onChange={(event) =>
                            form.setData('mail_host', event.target.value)
                        }
                        placeholder="smtp.hostinger.com"
                        dir="ltr"
                    />
                    {form.errors.mail_host ? (
                        <small>{form.errors.mail_host}</small>
                    ) : null}
                </label>
                <label>
                    <span>{t('infrastructure_settings.mail_port')}</span>
                    <input
                        className="form-control"
                        type="number"
                        min="1"
                        max="65535"
                        value={form.data.mail_port}
                        onChange={(event) =>
                            form.setData('mail_port', event.target.value)
                        }
                        dir="ltr"
                    />
                    {form.errors.mail_port ? (
                        <small>{form.errors.mail_port}</small>
                    ) : null}
                </label>
                <label>
                    <span>{t('infrastructure_settings.mail_scheme')}</span>
                    <select
                        className="form-select"
                        value={form.data.mail_scheme}
                        onChange={(event) =>
                            form.setData(
                                'mail_scheme',
                                event.target.value as 'smtp' | 'smtps',
                            )
                        }
                    >
                        <option value="smtps">
                            {t('infrastructure_settings.scheme_smtps')}
                        </option>
                        <option value="smtp">
                            {t('infrastructure_settings.scheme_smtp')}
                        </option>
                    </select>
                    {form.errors.mail_scheme ? (
                        <small>{form.errors.mail_scheme}</small>
                    ) : null}
                </label>
                <label>
                    <span>{t('infrastructure_settings.mail_username')}</span>
                    <input
                        className="form-control"
                        value={form.data.mail_username}
                        onChange={(event) =>
                            form.setData('mail_username', event.target.value)
                        }
                        autoComplete="username"
                        dir="ltr"
                    />
                    {form.errors.mail_username ? (
                        <small>{form.errors.mail_username}</small>
                    ) : null}
                </label>
                <label>
                    <span>{t('infrastructure_settings.mail_password')}</span>
                    <input
                        className="form-control"
                        type="password"
                        value={form.data.mail_password}
                        onChange={(event) =>
                            form.setData('mail_password', event.target.value)
                        }
                        placeholder={
                            passwordConfigured
                                ? t('infrastructure_settings.password_saved')
                                : ''
                        }
                        autoComplete="new-password"
                        dir="ltr"
                    />
                    <small
                        className={
                            form.errors.mail_password ? '' : 'text-muted'
                        }
                    >
                        {form.errors.mail_password ??
                            t('infrastructure_settings.password_help')}
                    </small>
                </label>
                <label>
                    <span>{t('infrastructure_settings.from_address')}</span>
                    <input
                        className="form-control"
                        type="email"
                        value={form.data.mail_from_address}
                        onChange={(event) =>
                            form.setData(
                                'mail_from_address',
                                event.target.value,
                            )
                        }
                        dir="ltr"
                    />
                    {form.errors.mail_from_address ? (
                        <small>{form.errors.mail_from_address}</small>
                    ) : null}
                </label>
                <label>
                    <span>{t('infrastructure_settings.from_name')}</span>
                    <input
                        className="form-control"
                        value={form.data.mail_from_name}
                        onChange={(event) =>
                            form.setData('mail_from_name', event.target.value)
                        }
                    />
                    {form.errors.mail_from_name ? (
                        <small>{form.errors.mail_from_name}</small>
                    ) : null}
                </label>
            </div>

            {passwordConfigured ? (
                <label className="pmc-infrastructure-clear-secret">
                    <input
                        type="checkbox"
                        checked={form.data.clear_mail_password}
                        onChange={(event) =>
                            form.setData(
                                'clear_mail_password',
                                event.target.checked,
                            )
                        }
                    />
                    <span>{t('infrastructure_settings.clear_password')}</span>
                </label>
            ) : null}
        </section>
    );
}
