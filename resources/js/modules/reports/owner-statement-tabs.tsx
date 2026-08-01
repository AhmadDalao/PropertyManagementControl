import { useTranslator } from '@/lib/i18n';

import type { OwnerStatementTab } from './types';

const tabs: Array<{
    key: OwnerStatementTab;
    label: `reports.${string}`;
    icon: string;
}> = [
    { key: 'overview', label: 'reports.tab_overview', icon: 'bi-grid' },
    {
        key: 'comparison',
        label: 'reports.comparison_title',
        icon: 'bi-graph-up-arrow',
    },
    {
        key: 'arrears',
        label: 'reports.contracts_in_arrears',
        icon: 'bi-exclamation-circle',
    },
    {
        key: 'payments',
        label: 'reports.recent_payments',
        icon: 'bi-cash-stack',
    },
    {
        key: 'maintenance',
        label: 'reports.maintenance_backlog',
        icon: 'bi-tools',
    },
];

export function OwnerStatementTabs({
    active,
    onSelect,
}: {
    active: OwnerStatementTab;
    onSelect: (tab: OwnerStatementTab) => void;
}) {
    const { t } = useTranslator();

    return (
        <nav
            className="pmc-report-tabs pmc-owner-statement-tabs"
            aria-label={t('reports.sections')}
            role="tablist"
        >
            {tabs.map((tab) => (
                <button
                    key={tab.key}
                    id={`owner-statement-tab-${tab.key}`}
                    type="button"
                    role="tab"
                    aria-controls="owner-statement-panel"
                    aria-selected={active === tab.key}
                    className={active === tab.key ? 'is-active' : ''}
                    onClick={() => onSelect(tab.key)}
                >
                    <i className={`bi ${tab.icon}`} aria-hidden="true" />
                    {t(tab.label)}
                </button>
            ))}
        </nav>
    );
}

export function isOwnerStatementTab(
    value: string | null,
): value is OwnerStatementTab {
    return tabs.some((tab) => tab.key === value);
}
