import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/payments/workspace.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { PaymentDetailWorkspace } from './payment-detail-workspace';
import type { PaymentDetailPageProps } from './types';

export default function PaymentDetailPage() {
    const { props } = usePage<PaymentDetailPageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <PaymentDetailWorkspace detail={props.detailPage} />
        </AdminLayout>
    );
}
