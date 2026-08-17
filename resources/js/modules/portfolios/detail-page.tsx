import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/portfolios/detail.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { PortfolioDetailWorkspace } from './detail/portfolio-detail-workspace';
import type { PortfolioDetailPageProps } from './detail/types';

export default function PortfolioDetailPage() {
    const { props } = usePage<PortfolioDetailPageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <PortfolioDetailWorkspace detail={props.detailPage} />
        </AdminLayout>
    );
}
