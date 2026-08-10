import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import { SchedulerCommand } from './scheduler-command';
import { SmtpSettingsFields } from './smtp-settings-fields';
import type {
    InfrastructureSettings,
    InfrastructureSettingsInput,
} from './types';

export function InfrastructureSettingsForm({
    settings,
    testTarget,
}: {
    settings: InfrastructureSettings;
    testTarget: string;
}) {
    const { t } = useTranslator();
    const [testBusy, setTestBusy] = useState(false);
    const form = useForm<InfrastructureSettingsInput>({
        mail_enabled: settings.mail_enabled,
        mail_host: settings.mail_host,
        mail_port: settings.mail_port,
        mail_scheme: settings.mail_scheme,
        mail_username: settings.mail_username,
        mail_password: '',
        clear_mail_password: false,
        mail_from_address: settings.mail_from_address,
        mail_from_name: settings.mail_from_name,
        scheduler_php_binary: settings.scheduler_php_binary,
    });
    const command = `${form.data.scheduler_php_binary} ${settings.scheduler_artisan_path} schedule:run`;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.put('/system/settings', {
            preserveScroll: true,
            onSuccess: () => form.reset('mail_password', 'clear_mail_password'),
        });
    };

    const testEmail = () => {
        setTestBusy(true);
        router.post(
            '/system/readiness/test-email',
            {},
            {
                preserveScroll: true,
                onFinish: () => setTestBusy(false),
            },
        );
    };

    return (
        <form onSubmit={submit} className="pmc-infrastructure-form">
            <SmtpSettingsFields
                form={form}
                passwordConfigured={settings.password_configured}
            />

            <section className="pmc-infrastructure-panel">
                <header>
                    <span className="pmc-infrastructure-panel-icon">
                        <i className="bi bi-clock-history" aria-hidden="true" />
                    </span>
                    <div>
                        <span>
                            {t('infrastructure_settings.scheduler_eyebrow')}
                        </span>
                        <h2>{t('infrastructure_settings.scheduler_title')}</h2>
                        <p>
                            {t('infrastructure_settings.scheduler_description')}
                        </p>
                    </div>
                </header>
                <label>
                    <span>{t('infrastructure_settings.php_binary')}</span>
                    <input
                        className="form-control"
                        value={form.data.scheduler_php_binary}
                        onChange={(event) =>
                            form.setData(
                                'scheduler_php_binary',
                                event.target.value,
                            )
                        }
                        dir="ltr"
                    />
                    {form.errors.scheduler_php_binary ? (
                        <small>{form.errors.scheduler_php_binary}</small>
                    ) : null}
                </label>
                <div className="pmc-infrastructure-command-label">
                    {t('infrastructure_settings.cron_command')}
                </div>
                <SchedulerCommand command={command} />
                <ol className="pmc-infrastructure-steps">
                    <li>{t('infrastructure_settings.cron_step_one')}</li>
                    <li>{t('infrastructure_settings.cron_step_two')}</li>
                    <li>{t('infrastructure_settings.cron_step_three')}</li>
                </ol>
            </section>

            <section className="pmc-infrastructure-security-note">
                <i className="bi bi-shield-lock" aria-hidden="true" />
                <div>
                    <strong>
                        {t('infrastructure_settings.security_title')}
                    </strong>
                    <p>{t('infrastructure_settings.security_description')}</p>
                </div>
            </section>

            <footer className="pmc-infrastructure-actions">
                <div>
                    <span>{t('infrastructure_settings.test_target')}</span>
                    <strong dir="ltr">{testTarget}</strong>
                </div>
                <button
                    type="button"
                    className="btn btn-outline-secondary"
                    onClick={testEmail}
                    disabled={!settings.smtp_ready || testBusy}
                >
                    <i className="bi bi-envelope-check" aria-hidden="true" />
                    {testBusy
                        ? t('infrastructure_settings.sending_test')
                        : t('infrastructure_settings.send_test')}
                </button>
                <button
                    type="submit"
                    className="btn btn-primary"
                    disabled={form.processing}
                >
                    {form.processing
                        ? t('infrastructure_settings.saving')
                        : t('infrastructure_settings.save')}
                </button>
            </footer>
        </form>
    );
}
