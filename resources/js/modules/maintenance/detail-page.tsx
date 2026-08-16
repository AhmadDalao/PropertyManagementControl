import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/maintenance/workspace.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { MaintenanceDetailWorkspace } from './maintenance-detail-workspace';
import type { MaintenanceDetailPageProps } from './types';

export default function MaintenanceDetailPage() {
    const { props } = usePage<MaintenanceDetailPageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <MaintenanceDetailWorkspace detail={props.detailPage} />
        </AdminLayout>
    );
}
