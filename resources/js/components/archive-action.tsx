import { router } from '@inertiajs/react';
import { useState } from 'react';

type ArchiveActionProps = {
    href: string;
    label?: string;
    confirmMessage: string;
    busyLabel?: string;
    className?: string;
};

export function ArchiveAction({
    href,
    label = 'Archive',
    confirmMessage,
    busyLabel = 'Working...',
    className = '',
}: ArchiveActionProps) {
    const [busy, setBusy] = useState(false);

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
            {busy ? busyLabel : label}
        </button>
    );
}
