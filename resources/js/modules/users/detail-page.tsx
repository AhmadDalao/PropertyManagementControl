import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/users/detail.css';

import { AdminLayout } from '@/layouts/admin-layout';

import type { UserDetailPageProps } from './detail/types';
import { UserDetailWorkspace } from './detail/user-detail-workspace';

export default function UserDetailPage() {
    const { props } = usePage<UserDetailPageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <UserDetailWorkspace detail={props.detailPage} />
        </AdminLayout>
    );
}
