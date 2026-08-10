import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

export function SchedulerCommand({ command }: { command: string }) {
    const { t } = useTranslator();
    const [copyStatus, setCopyStatus] = useState<'idle' | 'copied' | 'failed'>(
        'idle',
    );

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(command);
            setCopyStatus('copied');
        } catch {
            setCopyStatus('failed');
        }
    };

    const label =
        copyStatus === 'copied'
            ? t('infrastructure_settings.command_copied')
            : copyStatus === 'failed'
              ? t('infrastructure_settings.command_copy_failed')
              : t('infrastructure_settings.copy_command');

    return (
        <div className="pmc-infrastructure-command">
            <code dir="ltr">{command}</code>
            <button
                type="button"
                className="btn btn-outline-secondary"
                onClick={copy}
            >
                <i className="bi bi-copy" aria-hidden="true" />
                {label}
            </button>
            <span className="visually-hidden" aria-live="polite">
                {copyStatus === 'idle' ? '' : label}
            </span>
        </div>
    );
}
