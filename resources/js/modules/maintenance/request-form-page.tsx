import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/maintenance/workspace.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { MaintenanceRequestFormWorkspace } from './request-form-workspace';
import type { MaintenanceRequestFormPageProps } from './types';

export default function MaintenanceRequestFormPage() {
    const { props } = usePage<MaintenanceRequestFormPageProps>();

    return (
        <AdminLayout>
            <Head title={props.formPage.title} />
            <MaintenanceRequestFormWorkspace page={props.formPage} />
        </AdminLayout>
    );
}
