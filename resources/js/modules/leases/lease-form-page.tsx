import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/leases/form.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { LeaseFormWorkspace } from './lease-form-workspace';
import type { LeaseFormPageProps } from './types';

export default function LeaseFormPage() {
    const { props } = usePage<LeaseFormPageProps>();

    return (
        <AdminLayout>
            <Head title={props.formPage.title} />
            <LeaseFormWorkspace page={props.formPage} />
        </AdminLayout>
    );
}
