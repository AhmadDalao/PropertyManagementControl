import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/assets/detail.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { AssetDetailWorkspace } from './detail/asset-detail-workspace';
import type { AssetDetailPageProps } from './detail/types';

export default function AssetDetailPage() {
    const { props } = usePage<AssetDetailPageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <AssetDetailWorkspace detail={props.detailPage} />
        </AdminLayout>
    );
}
