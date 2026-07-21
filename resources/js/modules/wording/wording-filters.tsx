import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { WordingFilters, WordingGroupLabel } from './types';

export function WordingFiltersPanel({
    filters,
    groups,
    search,
    groupLabel,
    onSearch,
    onApply,
}: {
    filters: WordingFilters;
    groups: string[];
    search: string;
    groupLabel: WordingGroupLabel;
    onSearch: (value: string) => void;
    onApply: (overrides?: Record<string, string | number>) => void;
}) {
    const { t } = useTranslator();
    const submit = (event: FormEvent) => {
        event.preventDefault();
        onApply({ page: 1 });
    };

    return (
        <form className="pmc-wording-filters" onSubmit={submit}>
            <label className="pmc-wording-filter-search">
                <span>{t('wording.search')}</span>
                <div className="pmc-wording-search">
                    <i className="bi bi-search" />
                    <input
                        type="search"
                        value={search}
                        placeholder={t('wording.search_placeholder')}
                        onChange={(event) =>
                            onSearch(event.currentTarget.value)
                        }
                    />
                </div>
            </label>
            <label>
                <span>{t('wording.area')}</span>
                <select
                    className="form-select"
                    value={filters.group}
                    onChange={(event) =>
                        onApply({
                            group: event.currentTarget.value,
                            page: 1,
                        })
                    }
                >
                    <option value="all">{groupLabel('all')}</option>
                    {groups.map((group) => (
                        <option key={group} value={group}>
                            {groupLabel(group)}
                        </option>
                    ))}
                </select>
            </label>
            <label>
                <span>{t('wording.customized')}</span>
                <select
                    className="form-select"
                    value={filters.state}
                    onChange={(event) =>
                        onApply({
                            state: event.currentTarget.value,
                            page: 1,
                        })
                    }
                >
                    <option value="all">{t('wording.all_states')}</option>
                    <option value="customized">
                        {t('wording.customized_only')}
                    </option>
                    <option value="default">
                        {t('wording.defaults_only')}
                    </option>
                </select>
            </label>
            <button type="submit" className="btn btn-primary">
                <i className="bi bi-funnel" />
                {t('actions.filter')}
            </button>
        </form>
    );
}
