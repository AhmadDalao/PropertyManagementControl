import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

export function ReadinessCommand({ command }: { command: string }) {
    const { t } = useTranslator();
    const [status, setStatus] = useState<'idle' | 'copied' | 'failed'>('idle');

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(command);
            setStatus('copied');
        } catch {
            setStatus('failed');
        }
    };

    const buttonLabel =
        status === 'copied'
            ? t('readiness.command_copied')
            : status === 'failed'
              ? t('readiness.command_copy_failed')
              : t('readiness.copy_command');

    return (
        <div className="pmc-readiness-command">
            <div className="pmc-readiness-command-heading">
                <span>{t('readiness.cron_setup_title')}</span>
                <p>{t('readiness.cron_setup_description')}</p>
            </div>
            <ol>
                <li>{t('readiness.cron_step_open')}</li>
                <li>
                    {t('readiness.cron_step_schedule')}{' '}
                    <code dir="ltr">* * * * *</code>
                </li>
                <li>{t('readiness.cron_step_save')}</li>
            </ol>
            <span>{t('readiness.cron_command')}</span>
            <code dir="ltr">{command}</code>
            <button type="button" onClick={copy}>
                <i
                    className={`bi ${status === 'copied' ? 'bi-check2' : 'bi-copy'}`}
                    aria-hidden="true"
                />
                {buttonLabel}
            </button>
            <p className="pmc-readiness-command-note">
                <i className="bi bi-clock-history" aria-hidden="true" />
                {t('readiness.cron_verify_note')}
            </p>
            <span className="visually-hidden" aria-live="polite">
                {status === 'idle' ? '' : buttonLabel}
            </span>
        </div>
    );
}
