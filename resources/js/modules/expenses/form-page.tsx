import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/expenses/form.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { ExpenseFormWorkspace } from './form/expense-form-workspace';
import type { ExpenseFormPageProps } from './types';

export default function ExpenseFormPage() {
    const { props } = usePage<ExpenseFormPageProps>();

    return (
        <AdminLayout>
            <Head title={props.formPage.title} />
            <ExpenseFormWorkspace page={props.formPage} />
        </AdminLayout>
    );
}
