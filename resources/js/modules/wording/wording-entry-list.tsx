import { useTranslator } from '@/lib/i18n';

import type { WordingEntry, WordingGroupLabel } from './types';

export function WordingEntryList({
    entries,
    groupLabel,
    onSelect,
}: {
    entries: WordingEntry[];
    groupLabel: WordingGroupLabel;
    onSelect: (entry: WordingEntry) => void;
}) {
    const { t } = useTranslator();

    if (entries.length === 0) {
        return (
            <div className="pmc-wording-empty">
                <i className="bi bi-search" />
                <strong>{t('wording.no_results')}</strong>
                <p>{t('wording.no_results_description')}</p>
            </div>
        );
    }

    return (
        <div className="pmc-wording-list" aria-live="polite">
            {entries.map((entry) => (
                <button
                    key={`${entry.group}:${entry.key}`}
                    type="button"
                    className="pmc-wording-row"
                    onClick={() => onSelect(entry)}
                >
                    <span
                        className={`pmc-wording-state ${entry.customized ? 'is-custom' : ''}`}
                    >
                        {entry.customized
                            ? t('wording.custom_badge')
                            : t('wording.system_default')}
                    </span>
                    <span>
                        <small>{groupLabel(entry.group)}</small>
                        <strong>{entry.english}</strong>
                        <em dir="rtl">{entry.arabic}</em>
                    </span>
                    <code>
                        {entry.group}.{entry.key}
                    </code>
                    <i className="bi bi-chevron-right" />
                </button>
            ))}
        </div>
    );
}
