import type { KeyboardEvent } from 'react';

import { localizedNumber } from '@/lib/utils';

export type SystemWorkspaceSection =
    'readiness' | 'activity' | 'company' | 'website';

export type SystemWorkspaceOption = {
    key: SystemWorkspaceSection;
    label: string;
    count: number;
};

export function SystemWorkspaceTabs({
    active,
    label,
    locale,
    options,
    onSelect,
}: {
    active: SystemWorkspaceSection;
    label: string;
    locale: string;
    options: SystemWorkspaceOption[];
    onSelect: (section: SystemWorkspaceSection) => void;
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
        document.getElementById(`dashboard-system-tab-${next}`)?.focus();
    };

    return (
        <div
            className="pmc-dashboard-system-tabs"
            role="tablist"
            aria-label={label}
        >
            {options.map((option, index) => (
                <button
                    key={option.key}
                    id={`dashboard-system-tab-${option.key}`}
                    type="button"
                    role="tab"
                    className={active === option.key ? 'is-active' : ''}
                    data-dashboard-system-tab={option.key}
                    aria-selected={active === option.key}
                    aria-controls={`dashboard-system-panel-${option.key}`}
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
