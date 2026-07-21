import { useTranslator } from '@/lib/i18n';

import type {
    WordingFilterOverrides,
    WordingGroupLabel,
    WordingPageProps,
} from './types';
import { WordingEntryList } from './wording-entry-list';
import { WordingFiltersPanel } from './wording-filters';
import { WordingPagination } from './wording-pagination';

export function WordingCatalog({
    entries,
    groups,
    filters,
    search,
    groupLabel,
    onSearch,
    onApply,
    onSelect,
}: {
    entries: WordingPageProps['entries'];
    groups: string[];
    filters: WordingPageProps['filters'];
    search: string;
    groupLabel: WordingGroupLabel;
    onSearch: (value: string) => void;
    onApply: (overrides?: WordingFilterOverrides) => void;
    onSelect: (entry: WordingPageProps['entries']['data'][number]) => void;
}) {
    const { t } = useTranslator();

    return (
        <section className="pmc-wording-workspace">
            <header>
                <div>
                    <span>{t('wording.portal_title')}</span>
                    <strong>
                        {t('wording.showing', undefined, {
                            count: entries.total,
                        })}
                    </strong>
                </div>
            </header>
            <WordingFiltersPanel
                filters={filters}
                groups={groups}
                search={search}
                groupLabel={groupLabel}
                onSearch={onSearch}
                onApply={onApply}
            />
            <WordingEntryList
                entries={entries.data}
                groupLabel={groupLabel}
                onSelect={onSelect}
            />
            {entries.data.length > 0 ? (
                <WordingPagination
                    current={entries.current_page}
                    last={entries.last_page}
                    onPage={(page) => onApply({ page })}
                />
            ) : null}
        </section>
    );
}
