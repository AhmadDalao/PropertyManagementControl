import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/expenses/detail.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { ExpenseDetailWorkspace } from './detail/expense-detail-workspace';
import type { ExpenseDetailPageProps } from './detail/types';

export default function ExpenseDetailPage() {
    const { props } = usePage<ExpenseDetailPageProps>();

    return (
        <AdminLayout>
            <Head title={props.detailPage.header.title} />
            <ExpenseDetailWorkspace detail={props.detailPage} />
        </AdminLayout>
    );
}
