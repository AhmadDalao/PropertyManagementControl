import { usePage } from '@inertiajs/react';

import type { SharedProps } from '@/types';

export function FlashBanner() {
    const {
        flash: { success, error, warning, status },
    } = usePage<SharedProps>().props;

    if (!success && !error && !warning && !status) {
        return null;
    }

    const message = success ?? status ?? warning ?? error;
    const tone = error
        ? 'alert-danger'
        : warning
          ? 'alert-warning'
          : 'alert-success';

    return (
        <div className={`alert ${tone} mb-4 border-0`} role="alert">
            {message}
        </div>
    );
}
