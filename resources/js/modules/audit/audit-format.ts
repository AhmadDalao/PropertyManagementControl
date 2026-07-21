export function formatAuditDate(
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

export function auditEventTone(
    event: string,
): 'success' | 'warning' | 'danger' | 'neutral' | 'blue' {
    if (event === 'created') {
        return 'success';
    }

    if (event === 'updated') {
        return 'blue';
    }

    if (event === 'deleted') {
        return 'danger';
    }

    return 'neutral';
}
