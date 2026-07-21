import type { RefObject } from 'react';

import { useTranslator } from '@/lib/i18n';

import { SearchField } from './search-field';
import type { GlobalSearchResponse, GlobalSearchResult } from './types';

export function MobileSearchSheet({
    open,
    triggerRef,
    close,
    query,
    placeholder,
    loading,
    payload,
    groupedResults,
    setQuery,
    setResultsOpen,
}: {
    open: boolean;
    triggerRef: RefObject<HTMLButtonElement | null>;
    close: () => void;
    query: string;
    placeholder: string;
    loading: boolean;
    payload: GlobalSearchResponse | null;
    groupedResults: Record<string, GlobalSearchResult[]>;
    setQuery: (value: string) => void;
    setResultsOpen: (value: boolean) => void;
}) {
    const { t } = useTranslator();

    return (
        <>
            <button
                ref={triggerRef}
                type="button"
                className="pmc-mobile-search-trigger"
                aria-label={t('actions.search')}
                aria-expanded={open}
                onClick={() => setResultsOpen(true)}
                data-search-trigger
            >
                <i className="bi bi-search" />
            </button>

            {open ? (
                <div
                    className="pmc-mobile-search-sheet"
                    role="dialog"
                    aria-modal="true"
                    aria-label={t('actions.search')}
                >
                    <div className="pmc-mobile-search-head">
                        <strong>{t('shell.global_search')}</strong>
                        <button
                            type="button"
                            aria-label={t('common.close')}
                            onClick={close}
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
                        setOpen={setResultsOpen}
                        resultsId="pmc-global-search-results-mobile"
                        autoFocus
                    />
                </div>
            ) : null}
        </>
    );
}
