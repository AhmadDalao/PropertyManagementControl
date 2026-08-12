import { Head } from '@inertiajs/react';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { ManagementCommandCenter } from '../operations/management-command-center';
import type { OperationsDashboardProps } from '../types';

export function OperationsDashboard({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { text } = useTranslator();

    return (
        <AdminLayout>
            <Head title={text('Dashboard')} />
            <ManagementCommandCenter props={props} />
        </AdminLayout>
    );
}
