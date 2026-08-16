import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import type { ResourceField } from '@/components/resource-cycle';
import type {
    ResourceFormShellProps,
    ResourceFormValue,
    ResourceFormValues,
} from '@/components/resource-cycle/types';

export function useMaintenanceForm(page: ResourceFormShellProps) {
    const form = useForm<ResourceFormValues>(page.initialValues);
    const hasFile = page.fields.some((field) => field.type === 'file');

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const options = { preserveScroll: true, forceFormData: hasFile };

        if (page.method === 'put') {
            form.put(page.action, options);
        } else {
            form.post(page.action, options);
        }
    };

    const updateField = (field: ResourceField, value: ResourceFormValue) => {
        form.setData(field.name, value);

        if (!field.reloadOnChange) {
            return;
        }

        const queryValue =
            typeof value === 'string' || typeof value === 'number' ? value : '';

        router.get(
            window.location.pathname,
            { [field.reloadOnChange.queryKey]: queryValue },
            { preserveScroll: true, preserveState: false, replace: true },
        );
    };

    return { form, submit, updateField };
}
