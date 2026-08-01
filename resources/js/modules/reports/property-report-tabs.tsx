import { useTranslator } from '@/lib/i18n';

import type { PropertyReportTab } from './types';

const tabs: Array<{
    key: PropertyReportTab;
    label: `reports.${string}`;
    icon: string;
}> = [
    { key: 'overview', label: 'reports.tab_overview', icon: 'bi-grid' },
    {
        key: 'collections',
        label: 'reports.tab_collections',
        icon: 'bi-cash-stack',
    },
    { key: 'costs', label: 'reports.tab_costs', icon: 'bi-receipt' },
    {
        key: 'operations',
        label: 'reports.tab_operations',
        icon: 'bi-buildings',
    },
];

export function PropertyReportTabs({
    active,
    onSelect,
}: {
    active: PropertyReportTab;
    onSelect: (tab: PropertyReportTab) => void;
}) {
    const { t } = useTranslator();

    return (
        <nav
            className="pmc-report-tabs pmc-property-report-tabs"
            aria-label={t('reports.sections')}
        >
            {tabs.map((tab) => (
                <button
                    key={tab.key}
                    type="button"
                    className={active === tab.key ? 'is-active' : ''}
                    aria-current={active === tab.key ? 'page' : undefined}
                    onClick={() => onSelect(tab.key)}
                >
                    <i className={`bi ${tab.icon}`} aria-hidden="true" />
                    {t(tab.label)}
                </button>
            ))}
        </nav>
    );
}

export function isPropertyReportTab(
    value: string | null,
): value is PropertyReportTab {
    return tabs.some((tab) => tab.key === value);
}
