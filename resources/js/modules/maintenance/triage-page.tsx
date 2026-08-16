import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/maintenance/workspace.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { MaintenanceTriageWorkspace } from './triage-workspace';
import type { MaintenanceTriagePageProps } from './types';

export default function MaintenanceTriagePage() {
    const { props } = usePage<MaintenanceTriagePageProps>();

    return (
        <AdminLayout>
            <Head title={props.formPage.title} />
            <MaintenanceTriageWorkspace
                formPage={props.formPage}
                detailPage={props.detailPage}
            />
        </AdminLayout>
    );
}
