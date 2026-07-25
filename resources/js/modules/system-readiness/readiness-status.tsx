import { useTranslator } from '@/lib/i18n';

import type { ReadinessStatus } from './types';

const ICONS: Record<ReadinessStatus, string> = {
    ready: 'bi-check2-circle',
    attention: 'bi-exclamation-triangle',
    blocked: 'bi-shield-exclamation',
};

export function ReadinessStatusBadge({ status }: { status: ReadinessStatus }) {
    const { t } = useTranslator();

    return (
        <span className={`pmc-readiness-status is-${status}`}>
            <i className={`bi ${ICONS[status]}`} aria-hidden="true" />
            {t(`readiness.status_${status}`)}
        </span>
    );
}
