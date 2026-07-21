import { router } from '@inertiajs/react';
import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

type ArchiveActionProps = {
    href: string;
    label?: string;
    confirmMessage: string;
    busyLabel?: string;
    className?: string;
};

export function ArchiveAction({
    href,
    label,
    confirmMessage,
    busyLabel,
    className = '',
}: ArchiveActionProps) {
    const [busy, setBusy] = useState(false);
    const { t } = useTranslator();

    const submit = () => {
        if (!window.confirm(confirmMessage)) {
            return;
        }

        setBusy(true);
        router.delete(href, {
            preserveScroll: true,
            onFinish: () => setBusy(false),
        });
    };

    return (
        <button
            type="button"
            className={`btn btn-outline-danger btn-sm ${className}`}
            disabled={busy}
            onClick={submit}
        >
            {busy
                ? (busyLabel ?? t('actions.working'))
                : (label ?? t('actions.archive'))}
        </button>
    );
}
