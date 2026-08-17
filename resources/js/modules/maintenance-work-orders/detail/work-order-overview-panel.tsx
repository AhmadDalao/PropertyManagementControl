import { DetailCard } from '@/components/resource-cycle/detail-card';
import { WorkflowActionPanel } from '@/components/resource-cycle/workflow-action-panel';
import { useTranslator } from '@/lib/i18n';

import type { WorkOrderDetailPage } from './types';

export function WorkOrderOverviewPanel({
    detail,
}: {
    detail: WorkOrderDetailPage;
}) {
    const { t } = useTranslator();
    const context = detail.sections.find(
        (section) => section.key === 'context',
    );
    const scope = detail.sections.find((section) => section.key === 'scope');

    return (
        <div className="pmc-work-order-overview-grid">
            <main>
                <article className="pmc-work-order-summary">
                    <span aria-hidden="true">
                        <i className="bi bi-clipboard2-check" />
                    </span>
                    <div>
                        <small>{t('work_orders.controlled_job')}</small>
                        <h2>{detail.header.title}</h2>
                        <p>{detail.header.description}</p>
                    </div>
                </article>
                {scope ? <DetailCard section={scope} /> : null}
                {context ? <DetailCard section={context} /> : null}
            </main>
            <aside>
                <WorkflowActionPanel workflow={detail.workflow} />
            </aside>
        </div>
    );
}
