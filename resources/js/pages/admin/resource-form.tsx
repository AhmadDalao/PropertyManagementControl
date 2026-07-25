import { Head, usePage } from '@inertiajs/react';

import { ResourceFormShell } from '@/components/resource-cycle';
import type { ResourceFormShellProps } from '@/components/resource-cycle';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

type PageProps = SharedProps & {
    formPage: ResourceFormShellProps;
};

export default function ResourceFormPage() {
    const { props } = usePage<PageProps>();

    return (
        <AdminLayout>
            <Head title={props.formPage.title} />
            <ResourceFormShell {...props.formPage} />
        </AdminLayout>
    );
}
