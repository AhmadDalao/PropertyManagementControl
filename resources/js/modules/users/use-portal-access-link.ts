import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { PortalAccessLink } from './portal-access-types';

export type PortalAccessLinkStatus =
    'idle' | 'copied' | 'copy_failed' | 'error';

export function usePortalAccessLink(endpoint: string) {
    const { t } = useTranslator();
    const [link, setLink] = useState<PortalAccessLink | null>(null);
    const [busy, setBusy] = useState(false);
    const [status, setStatus] = useState<PortalAccessLinkStatus>('idle');
    const [error, setError] = useState('');

    const generate = async () => {
        setBusy(true);
        setStatus('idle');
        setError('');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
            });
            const payload = (await response.json()) as
                | PortalAccessLink
                | { message?: string; errors?: Record<string, string[]> };

            if (!response.ok) {
                const failure = payload as {
                    message?: string;
                    errors?: Record<string, string[]>;
                };

                throw new Error(
                    Object.values(failure.errors ?? {}).flat()[0] ??
                        failure.message,
                );
            }

            if (!('url' in payload)) {
                throw new Error(t('users.portal_access_generate_failed'));
            }

            setLink(payload);
        } catch (exception) {
            setError(
                exception instanceof Error && exception.message
                    ? exception.message
                    : t('users.portal_access_generate_failed'),
            );
            setStatus('error');
        } finally {
            setBusy(false);
        }
    };

    const copy = async () => {
        if (!link) {
            return;
        }

        try {
            await navigator.clipboard.writeText(link.url);
            setStatus('copied');
        } catch {
            setStatus('copy_failed');
        }
    };

    return { busy, copy, error, generate, link, status };
}

function xsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find((entry) => entry.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : '';
}
