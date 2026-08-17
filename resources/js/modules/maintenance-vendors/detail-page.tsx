import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/maintenance-vendors/detail.css';

import { AdminLayout } from '@/layouts/admin-layout';

import type { VendorDetailPageProps } from './detail/types';
import { VendorDetailWorkspace } from './detail/vendor-detail-workspace';

export default function MaintenanceVendorDetailPage() {
    const { props } = usePage<VendorDetailPageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <VendorDetailWorkspace detail={props.detailPage} />
        </AdminLayout>
    );
}
