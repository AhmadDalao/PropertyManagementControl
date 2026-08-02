import type { EmailDeliveryStatus } from './types';

export function deliveryStatusTone(
    status: EmailDeliveryStatus,
): 'success' | 'warning' | 'danger' {
    if (status === 'accepted') {
        return 'success';
    }

    if (status === 'failed') {
        return 'danger';
    }

    return 'warning';
}

export function formatDeliveryDate(
    value?: string | null,
    locale: string = 'en',
): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat(locale === 'ar' ? 'ar-SA' : 'en', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}
