import { DetailCard } from '@/components/resource-cycle/detail-card';

import { TenantNextStepPanel } from './tenant-next-step-panel';
import type { TenantDetailPage } from './types';

export function TenantOverviewPanel({ detail }: { detail: TenantDetailPage }) {
    const profile = detail.sections.find(
        (section) => section.key === 'profile',
    );

    return (
        <div className="pmc-tenant-overview-grid">
            <main>{profile ? <DetailCard section={profile} /> : null}</main>
            <aside>
                <TenantNextStepPanel workflow={detail.workflow} />
            </aside>
        </div>
    );
}
