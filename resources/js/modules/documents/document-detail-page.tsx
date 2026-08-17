import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/documents/detail.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { DocumentDetailWorkspace } from './detail/document-detail-workspace';
import type { DocumentDetailPageProps } from './detail/types';

export default function DocumentDetailPage() {
    const { props } = usePage<DocumentDetailPageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <DocumentDetailWorkspace detail={props.detailPage} />
        </AdminLayout>
    );
}
