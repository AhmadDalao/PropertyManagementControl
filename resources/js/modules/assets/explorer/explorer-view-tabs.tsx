import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { PropertyExplorerView } from './types';

type ExplorerViewOption = {
    key: PropertyExplorerView;
    label: string;
    count?: number;
};

export function ExplorerViewTabs({
    active,
    options,
    onSelect,
}: {
    active: PropertyExplorerView;
    options: ExplorerViewOption[];
    onSelect: (view: PropertyExplorerView) => void;
}) {
    const { locale, t } = useTranslator();

    return (
        <nav
            className="pmc-explorer-view-tabs"
            aria-label={t('assets.explorer.title')}
            style={{
                gridTemplateColumns: `repeat(${options.length}, minmax(0, 1fr))`,
            }}
        >
            {options.map((option) => (
                <button
                    key={option.key}
                    type="button"
                    className={active === option.key ? 'is-active' : ''}
                    data-explorer-view-tab={option.key}
                    aria-pressed={active === option.key}
                    onClick={() => onSelect(option.key)}
                >
                    <span>{option.label}</span>
                    {option.count !== undefined ? (
                        <strong>{localizedNumber(option.count, locale)}</strong>
                    ) : null}
                </button>
            ))}
        </nav>
    );
}
