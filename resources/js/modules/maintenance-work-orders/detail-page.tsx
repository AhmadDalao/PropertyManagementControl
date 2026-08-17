import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/maintenance-work-orders/detail.css';

import { AdminLayout } from '@/layouts/admin-layout';

import type { WorkOrderDetailPageProps } from './detail/types';
import { WorkOrderDetailWorkspace } from './detail/work-order-detail-workspace';

export default function WorkOrderDetailPage() {
    const { props } = usePage<WorkOrderDetailPageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <WorkOrderDetailWorkspace detail={props.detailPage} />
        </AdminLayout>
    );
}
