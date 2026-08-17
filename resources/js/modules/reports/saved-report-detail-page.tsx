import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/reports/saved-detail.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { SavedReportDetailWorkspace } from './saved-report-detail/saved-report-detail-workspace';
import type { SavedReportDetailPageProps } from './saved-report-detail/types';

export default function SavedReportDetailPage() {
    const { props } = usePage<SavedReportDetailPageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <SavedReportDetailWorkspace detail={props.detailPage} />
        </AdminLayout>
    );
}
