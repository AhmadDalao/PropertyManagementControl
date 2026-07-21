import { useTranslator } from '@/lib/i18n';

import type { SearchFieldProps } from './types';

export function SearchField({
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
}: SearchFieldProps) {
    const { t } = useTranslator();
    const showResults = open && query.trim().length >= 2;

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
                <span className="visually-hidden">{t('actions.search')}</span>
                <i className="bi bi-search" />
                <input
                    type="search"
                    value={query}
                    placeholder={placeholder}
                    onChange={(event) => setQuery(event.currentTarget.value)}
                    onFocus={() => setOpen(true)}
                    autoFocus={autoFocus}
                    aria-describedby={
                        showResults ? `${resultsId}-status` : undefined
                    }
                />
            </label>

            {showResults ? (
                <div
                    id={resultsId}
                    className="pmc-global-search-panel"
                    aria-live="polite"
                >
                    <p
                        id={`${resultsId}-status`}
                        className={`pmc-global-search-hint ${loading ? 'is-loading' : ''}`}
                    >
                        {loading
                            ? t('common.searching')
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
