import { useTranslator } from '@/lib/i18n';

import { humanLabel } from './human-label';

type StatusTone = 'success' | 'warning' | 'danger' | 'neutral' | 'blue';

export function StatusBadge({
    value,
    tone,
    label,
}: {
    value: string;
    tone?: StatusTone;
    label?: string;
}) {
    const { t } = useTranslator();
    const translated = t(
        `status.${value}` as `status.${string}`,
        t(`roles.${value}`, humanLabel(value)),
    );

    return (
        <span className={`pmc-status-badge is-${tone ?? statusTone(value)}`}>
            {label ?? translated}
        </span>
    );
}

function statusTone(value: string): StatusTone {
    if (['active', 'posted', 'paid', 'resolved', 'published'].includes(value)) {
        return 'success';
    }

    if (
        ['open', 'pending', 'draft', 'reserved', 'in_progress'].includes(value)
    ) {
        return 'warning';
    }

    if (
        ['blocked', 'suspended', 'overdue', 'cancelled', 'terminated'].includes(
            value,
        )
    ) {
        return 'danger';
    }

    if (['occupied', 'commercial', 'company'].includes(value)) {
        return 'blue';
    }

    return 'neutral';
}
