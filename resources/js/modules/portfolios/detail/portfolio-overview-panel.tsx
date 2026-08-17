import { DetailCard } from '@/components/resource-cycle/detail-card';
import { ResourceProgressPanel } from '@/components/resource-cycle/resource-progress-panel';

import { PortfolioNextStepPanel } from './portfolio-next-step-panel';
import type { PortfolioDetailPage } from './types';

export function PortfolioOverviewPanel({
    detail,
}: {
    detail: PortfolioDetailPage;
}) {
    const profile = detail.sections.find(
        (section) => section.key === 'profile',
    );

    return (
        <div className="pmc-portfolio-overview-grid">
            <main>
                {detail.progress ? (
                    <ResourceProgressPanel progress={detail.progress} />
                ) : null}
                {profile ? <DetailCard section={profile} /> : null}
            </main>
            <aside>
                <PortfolioNextStepPanel workflow={detail.workflow} />
            </aside>
        </div>
    );
}
