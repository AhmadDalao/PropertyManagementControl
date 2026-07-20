import { useEffect, useRef, useState } from 'react';

import { useTranslator } from '@/lib/i18n';
import type { GlobalSearchResponse, GlobalSearchResult } from '@/types';

export function GlobalSearch() {
    const [query, setQuery] = useState('');
    const [payload, setPayload] = useState<GlobalSearchResponse | null>(null);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);
    const mobileTriggerRef = useRef<HTMLButtonElement>(null);
    const { t } = useTranslator();
    const failedMessage = t('search.failed');

    useEffect(() => {
        const trimmed = query.trim();

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
                .then(
                    (response) =>
                        response.json() as Promise<GlobalSearchResponse>,
                )
                .then((nextPayload) => {
                    if (nextPayload.direct_url) {
                        window.location.href = nextPayload.direct_url;

                        return;
                    }

                    setPayload(nextPayload);
                    setOpen(true);
                })
                .catch((error: unknown) => {
                    if ((error as Error).name !== 'AbortError') {
                        setPayload({
                            ok: false,
                            query: trimmed,
                            results: [],
                            message: failedMessage,
                            direct_url: '',
                        });
                    }
                })
                .finally(() => setLoading(false));
        }, 240);

        return () => {
            controller.abort();
            window.clearTimeout(timer);
        };
    }, [failedMessage, query]);

    useEffect(() => {
        if (!mobileOpen) {
            return;
        }

        document.body.classList.add('pmc-search-open');

        const close = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setMobileOpen(false);
                window.requestAnimationFrame(() =>
                    mobileTriggerRef.current?.focus(),
                );
            }
        };

        document.addEventListener('keydown', close);

        return () => {
            document.removeEventListener('keydown', close);
            document.body.classList.remove('pmc-search-open');
        };
    }, [mobileOpen]);

    const groupedResults = groupResults(payload?.results ?? []);
    const placeholder = t(
        'shell.search_placeholder',
        'Search properties, tenants, leases...',
    );

    return (
        <>
            <SearchField
                className="pmc-global-search pmc-global-search-desktop"
                query={query}
                placeholder={placeholder}
                open={open}
                loading={loading}
                payload={payload}
                groupedResults={groupedResults}
                setQuery={setQuery}
                setOpen={setOpen}
                resultsId="pmc-global-search-results-desktop"
            />

            <button
                ref={mobileTriggerRef}
                type="button"
                className="pmc-mobile-search-trigger"
                aria-label={t('actions.search', 'Search')}
                onClick={() => setMobileOpen(true)}
            >
                <i className="bi bi-search" />
            </button>

            {mobileOpen ? (
                <div
                    className="pmc-mobile-search-sheet"
                    role="dialog"
                    aria-modal="true"
                    aria-label={t('actions.search', 'Search')}
                >
                    <div className="pmc-mobile-search-head">
                        <strong>
                            {t('shell.global_search', 'Global search')}
                        </strong>
                        <button
                            type="button"
                            aria-label={t('common.close', 'Close')}
                            onClick={() => {
                                setMobileOpen(false);
                                window.requestAnimationFrame(() =>
                                    mobileTriggerRef.current?.focus(),
                                );
                            }}
                        >
                            <i className="bi bi-x-lg" />
                        </button>
                    </div>
                    <SearchField
                        className="pmc-global-search pmc-global-search-mobile"
                        query={query}
                        placeholder={placeholder}
                        open
                        loading={loading}
                        payload={payload}
                        groupedResults={groupedResults}
                        setQuery={setQuery}
                        setOpen={setOpen}
                        resultsId="pmc-global-search-results-mobile"
                        autoFocus
                    />
                </div>
            ) : null}
        </>
    );
}

function SearchField({
    className,
    query,
    placeholder,
    open,
    loading,
    payload,
    groupedResults,
    setQuery,
    setOpen,
    resultsId,
    autoFocus = false,
}: {
    className: string;
    query: string;
    placeholder: string;
    open: boolean;
    loading: boolean;
    payload: GlobalSearchResponse | null;
    groupedResults: Record<string, GlobalSearchResult[]>;
    setQuery: (value: string) => void;
    setOpen: (value: boolean) => void;
    resultsId: string;
    autoFocus?: boolean;
}) {
    const { t } = useTranslator();

    return (
        <div
            className={className}
            onBlur={(event) => {
                if (!event.currentTarget.contains(event.relatedTarget)) {
                    setOpen(false);
                }
            }}
        >
            <label>
                <span className="visually-hidden">
                    {t('actions.search', 'Search')}
                </span>
                <i className="bi bi-search" />
                <input
                    type="search"
                    value={query}
                    placeholder={placeholder}
                    onChange={(event) => setQuery(event.currentTarget.value)}
                    onFocus={() => setOpen(true)}
                    autoFocus={autoFocus}
                    aria-controls={
                        open && query.trim().length >= 2 ? resultsId : undefined
                    }
                />
            </label>

            {open && query.trim().length >= 2 ? (
                <div
                    id={resultsId}
                    className="pmc-global-search-panel"
                    aria-live="polite"
                >
                    <p
                        className={`pmc-global-search-hint ${loading ? 'is-loading' : ''}`}
                    >
                        {loading
                            ? t('common.searching', 'Searching...')
                            : payload?.message ||
                              t('search.results_found', undefined, {
                                  count: payload?.results.length ?? 0,
                              })}
                    </p>
                    {Object.entries(groupedResults).map(([group, results]) => (
                        <section
                            key={group}
                            className="pmc-global-search-group"
                        >
                            <span>{group}</span>
                            {results.map((result) => (
                                <a
                                    key={`${result.group}-${result.title}-${result.url}`}
                                    href={result.url}
                                >
                                    <strong>{result.title}</strong>
                                    <small>{result.subtitle}</small>
                                    {result.badge ? (
                                        <em>{result.badge}</em>
                                    ) : null}
                                </a>
                            ))}
                        </section>
                    ))}
                </div>
            ) : null}
        </div>
    );
}

function groupResults(
    results: GlobalSearchResult[],
): Record<string, GlobalSearchResult[]> {
    return results.reduce<Record<string, GlobalSearchResult[]>>(
        (groups, result) => {
            groups[result.group] = [...(groups[result.group] ?? []), result];

            return groups;
        },
        {},
    );
}
