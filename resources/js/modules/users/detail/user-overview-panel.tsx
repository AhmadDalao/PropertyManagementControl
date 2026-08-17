import { DetailCard } from '@/components/resource-cycle/detail-card';

import type { UserDetailPage } from './types';
import { UserNextStepPanel } from './user-next-step-panel';

export function UserOverviewPanel({ detail }: { detail: UserDetailPage }) {
    const identity = detail.sections.find(
        (section) => section.key === 'identity',
    );

    return (
        <div className="pmc-user-overview-grid">
            <main>{identity ? <DetailCard section={identity} /> : null}</main>
            <aside>
                <UserNextStepPanel workflow={detail.workflow} />
            </aside>
        </div>
    );
}
