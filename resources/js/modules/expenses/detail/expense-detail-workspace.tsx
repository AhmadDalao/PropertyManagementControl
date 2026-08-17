import { useState } from 'react';

import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { ResourceHeader } from '@/components/resource-cycle/resource-header';

import { ExpenseDetailMetrics } from './expense-detail-metrics';
import {
    ExpenseDetailTabs,
    expenseTabs,
    requestedExpenseTab,
} from './expense-detail-tabs';
import { ExpenseEvidencePanel } from './expense-evidence-panel';
import { ExpenseFinancialPanel } from './expense-financial-panel';
import { ExpenseOverviewPanel } from './expense-overview-panel';
import type { ExpenseDetailPage, ExpenseDetailTab } from './types';

export function ExpenseDetailWorkspace({
    detail,
}: {
    detail: ExpenseDetailPage;
}) {
    const tabs = expenseTabs(detail);
    const available = tabs.map((tab) => tab.key);
    const [active, setActive] = useState<ExpenseDetailTab>(() =>
        requestedExpenseTab(available),
    );

    const selectTab = (tab: ExpenseDetailTab) => {
        setActive(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...detail.header} />
            <ExpenseDetailMetrics stats={detail.stats} />
            <ExpenseDetailTabs
                tabs={tabs}
                active={active}
                onSelect={selectTab}
            />
            <section
                className="pmc-expense-detail-panel"
                role="tabpanel"
                id="expense-detail-panel"
                aria-labelledby={`expense-tab-${active}`}
                tabIndex={0}
                data-testid="expense-detail-panel"
            >
                {active === 'overview' ? (
                    <ExpenseOverviewPanel detail={detail} />
                ) : null}
                {active === 'financial' ? (
                    <ExpenseFinancialPanel detail={detail} />
                ) : null}
                {active === 'evidence' ? (
                    <ExpenseEvidencePanel evidence={detail.evidence} />
                ) : null}
                {active === 'history' ? (
                    <HistoryTimeline timeline={detail.timeline} />
                ) : null}
            </section>
        </>
    );
}
