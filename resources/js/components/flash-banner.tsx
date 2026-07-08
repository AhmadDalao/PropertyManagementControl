import { usePage } from '@inertiajs/react';

import type { SharedProps } from '@/types';

export function FlashBanner() {
    const {
        flash: { success, error },
    } = usePage<SharedProps>().props;

    if (!success && !error) {
        return null;
    }

    return (
        <div
            className={`alert ${success ? 'alert-success' : 'alert-danger'} border-0 mb-4`}
            role="alert"
        >
            {success ?? error}
        </div>
    );
}
