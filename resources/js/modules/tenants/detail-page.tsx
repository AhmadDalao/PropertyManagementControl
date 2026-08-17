import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/tenants/detail.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { TenantDetailWorkspace } from './detail/tenant-detail-workspace';
import type { TenantDetailPageProps } from './detail/types';

export default function TenantDetailPage() {
    const { props } = usePage<TenantDetailPageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <TenantDetailWorkspace detail={props.detailPage} />
        </AdminLayout>
    );
}
