import { DetailCard } from '@/components/resource-cycle/detail-card';
import { WorkflowActionPanel } from '@/components/resource-cycle/workflow-action-panel';

import type { ExpenseDetailPage } from './types';

export function ExpenseOverviewPanel({
    detail,
}: {
    detail: ExpenseDetailPage;
}) {
    const context = detail.sections.find(
        (section) => section.key === 'context',
    );

    return (
        <div className="pmc-expense-overview-grid">
            <main>{context ? <DetailCard section={context} /> : null}</main>
            <aside>
                <WorkflowActionPanel workflow={detail.workflow} />
            </aside>
        </div>
    );
}
