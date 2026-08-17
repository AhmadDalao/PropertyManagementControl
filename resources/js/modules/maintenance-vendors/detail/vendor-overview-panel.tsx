import { DetailCard } from '@/components/resource-cycle/detail-card';
import { WorkflowActionPanel } from '@/components/resource-cycle/workflow-action-panel';
import { useTranslator } from '@/lib/i18n';

import type { VendorDetailPage } from './types';

export function VendorOverviewPanel({ detail }: { detail: VendorDetailPage }) {
    const { t } = useTranslator();
    const identity = detail.sections.find(
        (section) => section.key === 'identity',
    );
    const contact = detail.sections.find(
        (section) => section.key === 'contact',
    );

    return (
        <div className="pmc-vendor-overview-grid">
            <main>
                <article className="pmc-vendor-summary">
                    <span aria-hidden="true">
                        <i className="bi bi-buildings" />
                    </span>
                    <div>
                        <small>
                            {t('maintenance_vendors.controlled_contractor')}
                        </small>
                        <h2>{detail.header.title}</h2>
                        <p>{detail.header.description}</p>
                    </div>
                </article>
                {identity ? <DetailCard section={identity} /> : null}
                {contact ? <DetailCard section={contact} /> : null}
            </main>
            <aside>
                <WorkflowActionPanel workflow={detail.workflow} />
            </aside>
        </div>
    );
}
