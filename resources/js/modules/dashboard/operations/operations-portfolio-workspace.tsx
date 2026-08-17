import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { OperationsDashboardProps } from '../types';
import { LeaseExpiryPanel } from './lease-expiry-panel';
import { PortfolioHealthPanel } from './portfolio-health-panel';
import { PortfolioWorkspaceTabs } from './portfolio-workspace-tabs';
import type { PortfolioWorkspaceSection } from './portfolio-workspace-tabs';
import { PropertyPerformanceGrid } from './property-performance-grid';
import { RecentPaymentsPanel } from './recent-payments-panel';

const sections: PortfolioWorkspaceSection[] = [
    'properties',
    'health',
    'contracts',
    'activity',
];

export function OperationsPortfolioWorkspace({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t } = useTranslator();
    const [requested, setRequested] =
        useState<PortfolioWorkspaceSection>(initialSection);
    const active = sections.includes(requested) ? requested : 'properties';
    const select = (section: PortfolioWorkspaceSection) => {
        setRequested(section);
        const url = new URL(window.location.href);
        url.searchParams.set('portfolio_view', section);
        window.history.replaceState({}, '', url);
    };

    return (
        <div className="pmc-dashboard-portfolio-workspace">
            <PortfolioWorkspaceTabs
                active={active}
                label={t('dashboard.portfolio_workspace_navigation')}
                locale={locale}
                options={[
                    {
                        key: 'properties',
                        label: t('dashboard.properties'),
                        count: props.propertyPerformance.length,
                    },
                    {
                        key: 'health',
                        label: t('dashboard.portfolio_health'),
                        count: 3,
                    },
                    {
                        key: 'contracts',
                        label: t('dashboard.contracts'),
                        count: props.expiringLeases.length,
                    },
                    {
                        key: 'activity',
                        label: t('dashboard.activity'),
                        count: props.recentPayments.length,
                    },
                ]}
                onSelect={select}
            />

            <div
                id={`dashboard-portfolio-panel-${active}`}
                className="pmc-dashboard-portfolio-panel"
                data-dashboard-portfolio-panel={active}
                role="tabpanel"
                aria-labelledby={`dashboard-portfolio-tab-${active}`}
            >
                {active === 'properties' ? (
                    <PropertyPerformanceGrid props={props} />
                ) : null}
                {active === 'health' ? (
                    <PortfolioHealthPanel props={props} />
                ) : null}
                {active === 'contracts' ? (
                    <LeaseExpiryPanel props={props} />
                ) : null}
                {active === 'activity' ? (
                    <RecentPaymentsPanel props={props} />
                ) : null}
            </div>
        </div>
    );
}

function initialSection(): PortfolioWorkspaceSection {
    if (typeof window === 'undefined') {
        return 'properties';
    }

    const requested = new URLSearchParams(window.location.search).get(
        'portfolio_view',
    );

    return sections.includes(requested as PortfolioWorkspaceSection)
        ? (requested as PortfolioWorkspaceSection)
        : 'properties';
}
