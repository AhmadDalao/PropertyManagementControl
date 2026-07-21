import { useEffect, useRef, useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { GlobalSearchResponse } from './types';

export function useGlobalSearch() {
    const [query, setQueryState] = useState('');
    const [payload, setPayload] = useState<GlobalSearchResponse | null>(null);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const sequence = useRef(0);
    const { t } = useTranslator();
    const failedMessage = t('search.failed');

    useEffect(() => {
        const trimmed = query.trim();
        const requestSequence = ++sequence.current;

        if (trimmed.length < 2) {
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            setLoading(true);

            fetch(`/global-search?q=${encodeURIComponent(trimmed)}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            })
                .then(async (response) => {
                    if (!response.ok) {
                        throw new Error(
                            `Search failed with ${response.status}`,
                        );
                    }

                    return (await response.json()) as GlobalSearchResponse;
                })
                .then((nextPayload) => {
                    if (requestSequence !== sequence.current) {
                        return;
                    }

                    if (nextPayload.direct_url) {
                        window.location.assign(nextPayload.direct_url);

                        return;
                    }

                    setPayload(nextPayload);
                    setOpen(true);
                })
                .catch((error: unknown) => {
                    if (
                        (error as Error).name !== 'AbortError' &&
                        requestSequence === sequence.current
                    ) {
                        setPayload({
                            ok: false,
                            query: trimmed,
                            results: [],
                            message: failedMessage,
                            direct_url: '',
                        });
                    }
                })
                .finally(() => {
                    if (requestSequence === sequence.current) {
                        setLoading(false);
                    }
                });
        }, 240);

        return () => {
            controller.abort();
            window.clearTimeout(timer);
        };
    }, [failedMessage, query]);

    function setQuery(value: string) {
        setQueryState(value);

        if (value.trim().length < 2) {
            setPayload(null);
            setLoading(false);
        }
    }

    return {
        query,
        setQuery,
        payload,
        loading,
        open,
        setOpen,
    };
}
