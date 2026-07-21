import { useTranslator } from '@/lib/i18n';

import type { ResourceDetailTab } from './types';

export type ResourceDetailTabDefinition = {
    key: ResourceDetailTab;
    label: string;
    icon: string;
};

type ResourceDetailTabsProps = {
    tabs: ResourceDetailTabDefinition[];
    activeTab: ResourceDetailTab;
    onSelect: (tab: ResourceDetailTab) => void;
};

export function ResourceDetailTabs({
    tabs,
    activeTab,
    onSelect,
}: ResourceDetailTabsProps) {
    const { t, text } = useTranslator();

    return (
        <>
            <label className="pmc-resource-tab-select">
                <i className="bi bi-layout-text-sidebar" />
                <span className="visually-hidden">
                    {t('resource.record_sections')}
                </span>
                <select
                    className="form-select"
                    value={activeTab}
                    onChange={(event) =>
                        onSelect(event.currentTarget.value as ResourceDetailTab)
                    }
                >
                    {tabs.map((tab) => (
                        <option key={tab.key} value={tab.key}>
                            {text(tab.label)}
                        </option>
                    ))}
                </select>
            </label>

            <nav
                className="pmc-resource-tabs"
                aria-label={t('resource.record_sections')}
            >
                {tabs.map((tab) => (
                    <button
                        key={tab.key}
                        type="button"
                        className={activeTab === tab.key ? 'active' : ''}
                        aria-current={
                            activeTab === tab.key ? 'page' : undefined
                        }
                        onClick={() => onSelect(tab.key)}
                    >
                        <i className={`bi ${tab.icon}`} />
                        {text(tab.label)}
                    </button>
                ))}
            </nav>
        </>
    );
}
