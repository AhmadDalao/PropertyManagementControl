import { DetailCard } from '@/components/resource-cycle/detail-card';

import { AssetNextStepPanel } from './asset-next-step-panel';
import type { AssetDetailPage } from './types';

export function AssetOverviewPanel({ detail }: { detail: AssetDetailPage }) {
    const profile = detail.sections.find(
        (section) => section.key === 'profile',
    );
    const ownership = detail.sections.find(
        (section) => section.key === 'ownership',
    );

    return (
        <div className="pmc-asset-overview-grid">
            <main>
                {profile ? <DetailCard section={profile} /> : null}
                {ownership ? <DetailCard section={ownership} /> : null}
            </main>
            <aside>
                <AssetNextStepPanel workflow={detail.workflow} />
            </aside>
        </div>
    );
}
