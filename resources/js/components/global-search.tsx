import { useEffect, useState } from 'react';

import type { GlobalSearchResponse, GlobalSearchResult } from '@/types';

export function GlobalSearch() {
    const [query, setQuery] = useState('');
    const [payload, setPayload] = useState<GlobalSearchResponse | null>(null);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);

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
                            message: 'Search failed. Try again.',
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
    }, [query]);

    const groupedResults = groupResults(payload?.results ?? []);

    return (
        <div
            className="pmc-global-search"
            onBlur={(event) => {
                if (!event.currentTarget.contains(event.relatedTarget)) {
                    setOpen(false);
                }
            }}
        >
            <label>
                <i className="bi bi-search" />
                <input
                    type="search"
                    value={query}
                    placeholder="Search properties, tenants, leases..."
                    onChange={(event) => setQuery(event.currentTarget.value)}
                    onFocus={() => setOpen(true)}
                />
            </label>

            {open && query.trim().length >= 2 ? (
                <div className="pmc-global-search-panel">
                    <p
                        className={`pmc-global-search-hint ${loading ? 'is-loading' : ''}`}
                    >
                        {loading
                            ? 'Searching...'
                            : payload?.message ||
                              `${payload?.results.length ?? 0} result${payload?.results.length === 1 ? '' : 's'} found.`}
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
