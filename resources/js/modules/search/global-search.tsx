import { useTranslator } from '@/lib/i18n';

import { groupSearchResults } from './group-results';
import { MobileSearchSheet } from './mobile-search-sheet';
import { SearchField } from './search-field';
import { useGlobalSearch } from './use-global-search';
import { useMobileSearch } from './use-mobile-search';

export function GlobalSearch() {
    const search = useGlobalSearch();
    const mobile = useMobileSearch();
    const { t } = useTranslator();
    const groupedResults = groupSearchResults(search.payload?.results ?? []);
    const placeholder = t('shell.search_placeholder');

    return (
        <>
            <SearchField
                className="pmc-global-search pmc-global-search-desktop"
                query={search.query}
                placeholder={placeholder}
                open={search.open}
                loading={search.loading}
                payload={search.payload}
                groupedResults={groupedResults}
                setQuery={search.setQuery}
                setOpen={search.setOpen}
                resultsId="pmc-global-search-results-desktop"
            />

            <MobileSearchSheet
                open={mobile.open}
                triggerRef={mobile.triggerRef}
                close={mobile.close}
                query={search.query}
                placeholder={placeholder}
                loading={search.loading}
                payload={search.payload}
                groupedResults={groupedResults}
                setQuery={search.setQuery}
                setResultsOpen={(open) => {
                    mobile.setOpen(open);
                    search.setOpen(open);
                }}
            />
        </>
    );
}
