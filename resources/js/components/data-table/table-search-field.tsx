import { useTranslator } from '@/lib/i18n';

export function TableSearchField({
    value,
    isSearching,
    onChange,
}: {
    value: string;
    isSearching: boolean;
    onChange: (value: string) => void;
}) {
    const { t } = useTranslator();

    return (
        <label className="pmc-table-search">
            <span className="visually-hidden">
                {t('actions.search', 'Search')}
            </span>
            <i className="bi bi-search" />
            <input
                type="search"
                className="form-control"
                aria-busy={isSearching}
                value={value}
                placeholder={t('table.search', 'Search records...')}
                onChange={(event) => onChange(event.currentTarget.value)}
            />
            {isSearching ? (
                <i
                    className="bi bi-arrow-repeat pmc-table-searching"
                    aria-hidden="true"
                />
            ) : null}
            <span className="visually-hidden" aria-live="polite">
                {isSearching ? t('common.searching') : ''}
            </span>
        </label>
    );
}
