import type { KeyboardEvent } from 'react';

import { localizedNumber } from '@/lib/utils';

export type OperationsDashboardView = 'today' | 'portfolio' | 'system';

type DashboardViewOption = {
    key: OperationsDashboardView;
    label: string;
    description: string;
    icon: string;
    count: number;
};

export function OperationsViewTabs({
    active,
    label,
    locale,
    options,
    onSelect,
}: {
    active: OperationsDashboardView;
    label: string;
    locale: string;
    options: DashboardViewOption[];
    onSelect: (view: OperationsDashboardView) => void;
}) {
    const selectFromKeyboard = (
        event: KeyboardEvent<HTMLButtonElement>,
        index: number,
    ) => {
        const keys = options.map((option) => option.key);
        let nextIndex = index;

        if (event.key === 'ArrowRight') {
            nextIndex = (index + 1) % keys.length;
        } else if (event.key === 'ArrowLeft') {
            nextIndex = (index - 1 + keys.length) % keys.length;
        } else if (event.key === 'Home') {
            nextIndex = 0;
        } else if (event.key === 'End') {
            nextIndex = keys.length - 1;
        } else {
            return;
        }

        event.preventDefault();
        const next = keys[nextIndex];
        onSelect(next);
        document.getElementById(`dashboard-view-${next}`)?.focus();
    };

    return (
        <div
            className="pmc-dashboard-view-tabs"
            role="tablist"
            aria-label={label}
        >
            {options.map((option, index) => (
                <button
                    key={option.key}
                    id={`dashboard-view-${option.key}`}
                    type="button"
                    role="tab"
                    className={active === option.key ? 'is-active' : ''}
                    data-dashboard-view-tab={option.key}
                    aria-selected={active === option.key}
                    aria-controls={`dashboard-panel-${option.key}`}
                    tabIndex={active === option.key ? 0 : -1}
                    onClick={() => onSelect(option.key)}
                    onKeyDown={(event) => selectFromKeyboard(event, index)}
                >
                    <span className="pmc-dashboard-view-icon">
                        <i className={`bi ${option.icon}`} aria-hidden="true" />
                    </span>
                    <span className="pmc-dashboard-view-copy">
                        <strong>{option.label}</strong>
                        <small>{option.description}</small>
                    </span>
                    <span className="pmc-dashboard-view-count">
                        {localizedNumber(option.count, locale)}
                    </span>
                </button>
            ))}
        </div>
    );
}
