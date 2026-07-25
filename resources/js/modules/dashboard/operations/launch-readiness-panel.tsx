import { Link } from '@inertiajs/react';

import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { OperationsDashboardProps } from '../types';

type ReadinessStatus = NonNullable<OperationsDashboardProps['readinessStatus']>;

export function LaunchReadinessPanel({ status }: { status: ReadinessStatus }) {
    const { locale, t } = useTranslator();

    return (
        <WorkspacePanel
            className="pmc-dashboard-launch-readiness"
            eyebrow={t('readiness.eyebrow')}
            title={t('dashboard.launch_control_title')}
            description={t('dashboard.launch_control_description')}
            action={{
                label: t('readiness.open_readiness'),
                href: '/system/readiness',
            }}
        >
            <div className="pmc-dashboard-status-grid">
                <Link href="/system/readiness">
                    <span>{t('dashboard.platform_gate_status')}</span>
                    <strong>{t(`readiness.status_${status.status}`)}</strong>
                </Link>
                <Link href="/system/readiness">
                    <span>{t('dashboard.automatic_blockers')}</span>
                    <strong>
                        {localizedNumber(status.automatic_blocked, locale)}
                    </strong>
                </Link>
                <Link href="/system/readiness">
                    <span>{t('dashboard.automatic_attention')}</span>
                    <strong>
                        {localizedNumber(status.automatic_attention, locale)}
                    </strong>
                </Link>
                <Link href="/system/readiness">
                    <span>{t('dashboard.evidence_remaining')}</span>
                    <strong>
                        {localizedNumber(status.evidence_remaining, locale)}
                    </strong>
                </Link>
            </div>
        </WorkspacePanel>
    );
}
