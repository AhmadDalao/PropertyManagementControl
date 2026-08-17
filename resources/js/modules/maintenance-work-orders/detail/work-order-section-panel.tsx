import { DetailCard } from '@/components/resource-cycle/detail-card';

import type { WorkOrderDetailPage, WorkOrderSectionKey } from './types';
import { WorkOrderGuidanceCard } from './work-order-guidance-card';

type GuidedSection = 'assignment' | 'schedule' | 'cost' | 'completion';

export function WorkOrderSectionPanel({
    detail,
    sectionKey,
}: {
    detail: WorkOrderDetailPage;
    sectionKey: GuidedSection;
}) {
    const section = detail.sections.find(
        (candidate) => candidate.key === (sectionKey as WorkOrderSectionKey),
    );

    return (
        <div className="pmc-work-order-section-layout">
            <main>{section ? <DetailCard section={section} /> : null}</main>
            <aside>
                <WorkOrderGuidanceCard notice={detail.notices[sectionKey]} />
            </aside>
        </div>
    );
}
