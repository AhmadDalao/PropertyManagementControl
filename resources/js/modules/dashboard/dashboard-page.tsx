import { usePage } from '@inertiajs/react';

import type { DashboardPageProps } from './types';
import { OperationsDashboard } from './views/operations-dashboard';
import { TenantDashboard } from './views/tenant-dashboard';

export default function DashboardPage() {
    const { props } = usePage<DashboardPageProps>();

    return props.mode === 'tenant' ? (
        <TenantDashboard props={props} />
    ) : (
        <OperationsDashboard props={props} />
    );
}
