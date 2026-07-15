import { Head, usePage } from '@inertiajs/react';

import { ResourceFormShell } from '@/components/resource-cycle';
import type {
    ResourceField,
    ResourceFormValues,
} from '@/components/resource-cycle';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

type PageProps = SharedProps & {
    formPage: {
        title: string;
        description: string;
        backHref: string;
        backLabel: string;
        action: string;
        method: 'post' | 'put';
        submitLabel: string;
        fields: ResourceField[];
        initialValues: ResourceFormValues;
    };
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
