import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/payments/workspace.css';
import '../../../css/styles/payments/lease-picker.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { PaymentFormWorkspace } from './payment-form-workspace';
import type { PaymentFormPageProps } from './types';

export default function PaymentFormPage() {
    const { props } = usePage<PaymentFormPageProps>();

    return (
        <AdminLayout>
            <Head title={props.formPage.title} />
            <PaymentFormWorkspace page={props.formPage} />
        </AdminLayout>
    );
}
