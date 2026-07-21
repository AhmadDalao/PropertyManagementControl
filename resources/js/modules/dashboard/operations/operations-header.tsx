import { WorkspaceHeader } from '@/components/operations';

import type { OperationsDashboardProps } from '../types';

export function OperationsHeader({
    mode,
}: {
    mode: OperationsDashboardProps['mode'];
}) {
    return (
        <WorkspaceHeader
            eyebrow={
                mode === 'superadmin'
                    ? 'Platform overview'
                    : 'Portfolio overview'
            }
            title="Property operations, at a glance."
            description="See the money, occupancy, contracts, and service work that need attention today."
            actions={[
                {
                    label: 'Create asset',
                    href: '/assets/create',
                    icon: 'bi-plus-lg',
                    tone: 'primary',
                },
                {
                    label: 'Create tenant',
                    href: '/tenants/create',
                    icon: 'bi-person-plus',
                },
                {
                    label: 'Reports',
                    href: '/reports',
                    icon: 'bi-bar-chart-line',
                    tone: 'quiet',
                },
            ]}
        />
    );
}
