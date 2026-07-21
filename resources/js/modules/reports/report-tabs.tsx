import { useTranslator } from '@/lib/i18n';

import type { ReportTab } from './types';

export const reportTabs: Array<{
    key: ReportTab;
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

export function ReportTabs({
    active,
    onSelect,
}: {
    active: ReportTab;
    onSelect: (tab: ReportTab) => void;
}) {
    const { t } = useTranslator();

    return (
        <nav className="pmc-report-tabs" aria-label={t('reports.sections')}>
            {reportTabs.map((tab) => (
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

export function isReportTab(value: string | null): value is ReportTab {
    return reportTabs.some((tab) => tab.key === value);
}
