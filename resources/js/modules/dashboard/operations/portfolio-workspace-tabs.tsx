import type { KeyboardEvent } from 'react';

import { localizedNumber } from '@/lib/utils';

export type PortfolioWorkspaceSection =
    'properties' | 'health' | 'contracts' | 'activity';

export type PortfolioWorkspaceOption = {
    key: PortfolioWorkspaceSection;
    label: string;
    count: number;
};

export function PortfolioWorkspaceTabs({
    active,
    label,
    locale,
    options,
    onSelect,
}: {
    active: PortfolioWorkspaceSection;
    label: string;
    locale: string;
    options: PortfolioWorkspaceOption[];
    onSelect: (section: PortfolioWorkspaceSection) => void;
}) {
    const selectFromKeyboard = (
        event: KeyboardEvent<HTMLButtonElement>,
        index: number,
    ) => {
        let nextIndex = index;
        const direction = locale === 'ar' ? -1 : 1;

        if (event.key === 'ArrowRight') {
            nextIndex = (index + direction + options.length) % options.length;
        } else if (event.key === 'ArrowLeft') {
            nextIndex = (index - direction + options.length) % options.length;
        } else if (event.key === 'Home') {
            nextIndex = 0;
        } else if (event.key === 'End') {
            nextIndex = options.length - 1;
        } else {
            return;
        }

        event.preventDefault();
        const next = options[nextIndex].key;
        onSelect(next);
        document.getElementById(`dashboard-portfolio-tab-${next}`)?.focus();
    };

    return (
        <div
            className="pmc-dashboard-portfolio-tabs"
            role="tablist"
            aria-label={label}
            data-dashboard-portfolio-tabs
        >
            {options.map((option, index) => (
                <button
                    key={option.key}
                    id={`dashboard-portfolio-tab-${option.key}`}
                    type="button"
                    role="tab"
                    className={active === option.key ? 'is-active' : ''}
                    data-dashboard-portfolio-tab={option.key}
                    aria-selected={active === option.key}
                    aria-controls={`dashboard-portfolio-panel-${option.key}`}
                    tabIndex={active === option.key ? 0 : -1}
                    onClick={() => onSelect(option.key)}
                    onKeyDown={(event) => selectFromKeyboard(event, index)}
                >
                    <span>{option.label}</span>
                    <strong>{localizedNumber(option.count, locale)}</strong>
                </button>
            ))}
        </div>
    );
}
