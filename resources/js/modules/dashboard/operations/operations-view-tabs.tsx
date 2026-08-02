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
    return (
        <nav className="pmc-dashboard-view-tabs" aria-label={label}>
            {options.map((option) => (
                <button
                    key={option.key}
                    id={`dashboard-view-${option.key}`}
                    type="button"
                    className={active === option.key ? 'is-active' : ''}
                    data-dashboard-view-tab={option.key}
                    aria-pressed={active === option.key}
                    onClick={() => onSelect(option.key)}
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
        </nav>
    );
}
