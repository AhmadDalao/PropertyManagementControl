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
            <span>{t('readiness.cron_command')}</span>
            <code dir="ltr">{command}</code>
            <button type="button" onClick={copy}>
                <i
                    className={`bi ${status === 'copied' ? 'bi-check2' : 'bi-copy'}`}
                    aria-hidden="true"
                />
                {buttonLabel}
            </button>
            <span className="visually-hidden" aria-live="polite">
                {status === 'idle' ? '' : buttonLabel}
            </span>
        </div>
    );
}
