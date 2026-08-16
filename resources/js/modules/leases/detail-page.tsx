import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/leases/detail.css';
import '../../../css/styles/leases/next-step.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { LeaseDetailWorkspace } from './lease-detail-workspace';
import type { LeaseDetailPageProps } from './types';

export default function LeaseDetailPage() {
    const { props } = usePage<LeaseDetailPageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <LeaseDetailWorkspace detail={props.detailPage} />
        </AdminLayout>
    );
}
