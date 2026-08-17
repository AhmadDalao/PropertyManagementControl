import { DetailCard } from '@/components/resource-cycle/detail-card';

import { SavedReportGuidanceCard } from './saved-report-guidance-card';
import type { SavedReportDetailPage } from './types';

export function SavedReportSectionPanel({
    detail,
    sectionKey,
}: {
    detail: SavedReportDetailPage;
    sectionKey: 'scope' | 'access';
}) {
    const section = detail.sections.find(
        (candidate) => candidate.key === sectionKey,
    );

    return (
        <div className="pmc-saved-report-section-layout">
            <main>{section ? <DetailCard section={section} /> : null}</main>
            <aside>
                <SavedReportGuidanceCard notice={detail.notices[sectionKey]} />
            </aside>
        </div>
    );
}
